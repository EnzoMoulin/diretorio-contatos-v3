#!/usr/bin/env python3
"""SHAs citados nos docs: nenhum aponta para material que nao existe mais.

Motivo: duas rodadas de revisao externa foram revisar um retrato que nao era
mais o estado do repositorio — um SHA existia como objeto mas estava fora da
`main` (tip de PR fechado por squash), outro estava morto no v2. O doc parecia
citavel e nao era. Esta checagem poe isso no PR, no dia em que o texto foi
escrito, em vez de numa sessao de revisao semanas depois.

O que e conferido: cada token `` `abc1234` `` em arquivo `.md` versionado, com
7 a 40 hex e nada mais dentro das crases (sha256 truncado, cor `#a1b2c3d` e
codigo do IBGE ficam de fora de proposito). Ancestralidade, nao existencia:
`git merge-base --is-ancestor` — SHA que resolve mas nao esta na historia da
`main` e o caso que causou o problema, e `git cat-file` sozinho nao pega.

Saida: por padrao e informativa e sai com 0 — no CI ela vira `::warning` na
linha do arquivo, para aparecer no PR sem travar merge de documentacao.
`--estrito` sai com 1 quando ha SHA fora da main ou que nao resolve: e o modo
do checklist de envio, nao do CI.

Uso:
    python3 scripts/verificar-shas-citados.py              # informativo
    python3 scripts/verificar-shas-citados.py --estrito    # codigo de saida 1
    python3 scripts/verificar-shas-citados.py --github     # forca anotacoes
    python3 scripts/verificar-shas-citados.py docs/plano-v3.md

SHA de outro repositorio (o v2, congelado) nao resolve aqui e nao e defeito:
vai declarado em `.shas-citados` na raiz, com o motivo. A allowlist nao e
silenciosa — o script imprime quantos citados ela abafou e qual declaracao
nunca casou com nada (candidata a sair), como a `.privacidade-sinteticos`.
"""
import argparse
import os
import re
import subprocess
import sys

ARQUIVO_ALLOWLIST = ".shas-citados"
ESTE_ARQUIVO = os.path.abspath(__file__)
SHA = re.compile(r"`([0-9a-f]{7,40})`")


def git(*args, repo=None):
    return subprocess.run(["git", "-C", repo or ".", *args],
                          capture_output=True, text=True)


def ler_allowlist(caminho):
    """Uma entrada por linha: `<sha>` ou `<sha>  # motivo`."""
    declarados = {}
    try:
        with open(caminho, encoding="utf-8") as fh:
            for linha in fh:
                linha = linha.split("#", 1)[0].strip() if not linha.lstrip().startswith("#") else ""
                if linha:
                    declarados[linha.lower()] = True
    except FileNotFoundError:
        pass
    return declarados


def arquivos_md(repo, alvos):
    if alvos:
        return alvos
    saida = git("ls-files", "*.md", repo=repo).stdout.splitlines()
    return [a for a in saida if os.path.abspath(os.path.join(repo, a)) != ESTE_ARQUIVO]


def resolver(sha, repo):
    """(existe_como_commit, ancestral_do_head)"""
    if git("cat-file", "-e", f"{sha}^{{commit}}", repo=repo).returncode != 0:
        return (False, False)
    return (True, git("merge-base", "--is-ancestor", sha, "HEAD", repo=repo).returncode == 0)


def main():
    ap = argparse.ArgumentParser(
        description="Confere os SHAs citados nos docs contra a historia da main.")
    ap.add_argument("alvos", nargs="*", help="arquivos .md (padrao: todos os versionados)")
    ap.add_argument("--repo", default=".")
    ap.add_argument("--estrito", action="store_true", help="sai com 1 se achar problema")
    ap.add_argument("--github", action="store_true", help="anotacoes ::warning/::notice")
    args = ap.parse_args()

    if git("rev-parse", "--git-dir", repo=args.repo).returncode != 0:
        print(f"nao e um repositorio git: {args.repo}")
        return 2
    raiz = git("rev-parse", "--show-toplevel", repo=args.repo).stdout.strip()
    if git("rev-parse", "--verify", "-q", "HEAD", repo=args.repo).returncode != 0:
        print("repositorio sem HEAD: nada a conferir")
        return 0

    allow = ler_allowlist(os.path.join(raiz, ARQUIVO_ALLOWLIST))
    usados = set()
    achados, fora, mortos, ok = [], [], [], 0

    for caminho in arquivos_md(args.repo, args.alvos):
        try:
            with open(os.path.join(raiz, caminho), encoding="utf-8") as fh:
                linhas = fh.readlines()
        except (FileNotFoundError, UnicodeDecodeError):
            continue
        for n, linha in enumerate(linhas, 1):
            for sha in SHA.findall(linha):
                if sha in allow:
                    usados.add(sha)
                    achados.append(("historico", caminho, n, sha))
                    continue
                existe, ancestral = resolver(sha, args.repo)
                if not existe:
                    achados.append(("nao_resolve", caminho, n, sha))
                    mortos.append((caminho, n, sha))
                elif not ancestral:
                    achados.append(("fora_da_main", caminho, n, sha))
                    fora.append((caminho, n, sha))
                else:
                    ok += 1

    anotacao = args.github or os.environ.get("GITHUB_ACTIONS") == "true"
    if anotacao:
        for tipo, caminho, n, sha in achados:
            if tipo == "fora_da_main":
                print(f"::warning file={caminho},line={n},title=SHA fora da main::"
                      f"`{sha}` resolve, mas nao esta na historia da main. "
                      f"Se o texto diz que e material historico, declare em "
                      f"{ARQUIVO_ALLOWLIST}; se nao, o doc esta citando retrato vencido.")
            elif tipo == "nao_resolve":
                print(f"::warning file={caminho},line={n},title=SHA nao resolve::"
                      f"`{sha}` nao existe como commit neste repositorio. Se e SHA "
                      f"do v2, declare em {ARQUIVO_ALLOWLIST}.")
            # `historico`: nada de anotacao por ocorrencia — sao 22 hoje e o
            # resumo do rodape ja conta. Anotacao em excesso faz a que importa
            # passar batida.
    else:
        for caminho, n, sha in fora:
            print(f"  fora da main  {caminho}:{n}  `{sha}`")
        for caminho, n, sha in mortos:
            print(f"  nao resolve   {caminho}:{n}  `{sha}`")
        if not fora and not mortos:
            print("  nenhum SHA fora da main ou morto")

    declarados = len([a for a in achados if a[0] == "historico"])
    orfaos = sorted(set(allow) - usados)
    print()
    print(f"SHAs citados: {ok} na main, {declarados} ocorrencia(s) historica(s) declarada(s), "
          f"{len(fora)} fora da main, {len(mortos)} nao resolvem.")
    if not args.alvos:
        print(f"allowlist {ARQUIVO_ALLOWLIST}: {len(allow)} declaracao(oes) — "
              f"{len(usados)} em uso, {len(orfaos)} sem citacao.")
        for sha in orfaos:
            print(f"  sem uso       `{sha}` — candidata a sair da allowlist")

    if args.estrito and (fora or mortos):
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
