#!/usr/bin/env python3
"""Contagens citadas: README, manifesto e seed nao podem discordar.

O que este script NAO faz, e por que: nao confere todo numero seguido de
unidade nos docs. Medido em 26/11, `N contatos` aparece com 1.807 (total), 286
(Sede), 166 (Vitoria), 137 e 84 (asseracoes de teste), 11 e 2 (fixture) — um
carimbo por unidade acusaria ~30 linhas corretas. Ruido assim faz a checagem
que importa passar batida.

O que ele confere, em duas metades:

1. Sem argumento (e o que o CI roda): o manifesto `.contagens` contra as
   declaracoes canonicas do `README.md` — o bloco "Numeros verificados" e as
   outras linhas que citam o total. Pega o caso comum: alguem atualiza o
   manifesto (ou o seed cresce) e o README fica contando a historia antiga.
2. Com `--seed <json>`: o manifesto contra o seed do v2, de onde as contagens
   vem de fato. Se o seed cresceu, ele lista **cada linha** dos docs que ainda
   cita o total antigo — o estrago medido, com arquivo e linha, em vez de um
   "desatualizado" sem tamanho.

Saida informativa por padrao (codigo 0, `::warning` na linha no CI);
`--estrito` sai com 1 e e o modo do checklist de envio.

Uso:
    python3 scripts/verificar-contagens.py
    python3 scripts/verificar-contagens.py --seed ../diretorio-contatos-v2/seed_mascarado.json
    python3 scripts/verificar-contagens.py --estrito

Mapeamento seed -> v3 (medido em 26/11, conferido no banco local):
    comarcas      = comarcas com `ibge` preenchido (69); a Sede nao e comarca
    comarcas_sede = linhas em `comarcas` (70 = 69 + Sede)
    locais        = `foruns` (72) + a Sede como local proprio (1)
    nos           = `nos`
    contatos      = `contatos`
    uteis         = `uteis`
"""
import argparse
import hashlib
import json
import os
import re
import subprocess
import sys

ARQUIVO_MANIFESTO = ".contagens"
ESTE_ARQUIVO = os.path.abspath(__file__)
SEED_PADRAO = ("../diretorio-contatos-v2/seed_mascarado.json",
               os.path.expanduser("~/Emprego/Projetos/diretorio-contatos-v2/seed_mascarado.json"))

# declaracao canonica no README -> nome no manifesto. A ordem importa: a Sede
# tem de casar antes do "69 com IBGE", senao o numero dela vira o das comarcas.
ANCORAS_README = (
    (re.compile(r"([\d.]+)\s+contatos"), "contatos"),
    (re.compile(r"([\d.]+)\s+nós"), "nos"),
    (re.compile(r"([\d.]+)\s+locais"), "locais"),
    (re.compile(r"([\d.]+)\s+entradas em `comarcas`"), "comarcas_sede"),
    (re.compile(r"\(([\d.]+)\s+com IBGE"), "comarcas"),
    (re.compile(r"([\d.]+)\s+úteis"), "uteis"),
)

# nas outras linhas e docs o total pode vir sem o rotulo da ancora
UNIDADE = {
    "contatos": re.compile(r"contatos?\b"),
    "nos": re.compile(r"nós\b|\bnos\b"),
    "locais": re.compile(r"locais\b"),
    "comarcas": re.compile(r"comarcas\b"),
    "uteis": re.compile(r"úteis\b"),
}


def git(*args, repo="."):
    return subprocess.run(["git", "-C", repo, *args], capture_output=True, text=True)


def numero(texto):
    """'1.807' -> 1807; '856' -> 856."""
    return int(texto.replace(".", ""))


def formatar(valor):
    """1807 -> '1.807', como os docs escrevem."""
    return f"{valor:,}".replace(",", ".")


def ler_manifesto(caminho):
    valores = {}
    try:
        with open(caminho, encoding="utf-8") as fh:
            for linha in fh:
                linha = linha.split("#", 1)[0].strip()
                if not linha:
                    continue
                partes = linha.split()
                if len(partes) >= 2:
                    valores[partes[0]] = partes[1]
    except FileNotFoundError:
        return None
    return valores


def contar_seed(caminho):
    with open(caminho, "rb") as fh:
        bruto = fh.read()
    d = json.loads(bruto.decode("utf-8"))
    return {
        "comarcas": sum(1 for c in d["comarcas"] if str(c.get("ibge", "")).strip()),
        "comarcas_sede": len(d["comarcas"]),
        "locais": len(d["foruns"]) + 1,
        "nos": len(d["nos"]),
        "contatos": len(d["contatos"]),
        "uteis": len(d["uteis"]),
        "seed_sha256": hashlib.sha256(bruto).hexdigest(),
    }


def ocorrencias(repo, valor, unidade, limite=10):
    """Linhas dos .md versionados que citam `valor <unidade>` — o estrago medido."""
    alvo = re.compile(rf"\b{re.escape(formatar(valor))}\b|\b{valor}\b")
    achados = []
    arquivos = git("ls-files", "*.md", repo=repo).stdout.splitlines()
    for caminho in arquivos:
        try:
            with open(os.path.join(repo, caminho), encoding="utf-8") as fh:
                for n, linha in enumerate(fh, 1):
                    if alvo.search(linha) and UNIDADE[unidade].search(linha):
                        achados.append((caminho, n))
        except (FileNotFoundError, UnicodeDecodeError):
            continue
    return achados[:limite], len(achados)


def main():
    ap = argparse.ArgumentParser(
        description="Confere as contagens citadas contra o manifesto e o seed do v2.")
    ap.add_argument("--repo", default=".")
    ap.add_argument("--seed", nargs="?", const="", default=None,
                    help="seed do v2; sem valor, procura no caminho padrao")
    ap.add_argument("--manifesto", default=None)
    ap.add_argument("--estrito", action="store_true")
    ap.add_argument("--github", action="store_true")
    args = ap.parse_args()

    if git("rev-parse", "--git-dir", repo=args.repo).returncode != 0:
        print(f"nao e um repositorio git: {args.repo}")
        return 2
    raiz = git("rev-parse", "--show-toplevel", repo=args.repo).stdout.strip()
    manifesto = ler_manifesto(args.manifesto or os.path.join(raiz, ARQUIVO_MANIFESTO))
    if manifesto is None:
        print(f"manifesto {ARQUIVO_MANIFESTO} nao encontrado em {raiz}")
        return 2

    anotacao = args.github or os.environ.get("GITHUB_ACTIONS") == "true"
    problemas = []

    def aviso(caminho, linha, titulo, texto):
        problemas.append((caminho, linha))
        if anotacao:
            print(f"::warning file={caminho},line={linha},title={titulo}::{texto}")
        else:
            print(f"  {titulo}  {caminho}:{linha}  {texto}")

    # --- 1. manifesto x README ------------------------------------------------
    readme = os.path.join(raiz, "README.md")
    if os.path.exists(readme):
        with open(readme, encoding="utf-8") as fh:
            linhas = fh.readlines()
        for nome, esperado in manifesto.items():
            if nome == "seed_sha256":
                continue
            for ancora, alvo in ANCORAS_README:
                if alvo != nome:
                    continue
                for n, linha in enumerate(linhas, 1):
                    m = ancora.search(linha)
                    if not m:
                        continue
                    lido = numero(m.group(1))
                    if lido != numero(esperado):
                        aviso("README.md", n, "Contagem diverge do manifesto",
                              f"README diz {formatar(lido)} {alvo}; manifesto diz "
                              f"{formatar(numero(esperado))}.")
                    break
                else:
                    # declaracao sumiu do README (texto reescrito): avisar em vez
                    # de passar por conferida — ancora que nao casa e ponto cego.
                    aviso("README.md", 1, "Declaracao nao encontrada",
                          f"README nao tem mais a declaracao canonica de {alvo}.")
                break

    # --- 2. manifesto x seed --------------------------------------------------
    seed = args.seed
    conferiu_seed = False
    if seed is None:
        for candidato in SEED_PADRAO:
            caminho = candidato if os.path.isabs(candidato) else os.path.join(raiz, candidato)
            if os.path.exists(caminho):
                seed = caminho
                break
    if seed:
        if not os.path.exists(seed):
            print(f"seed nao encontrado em {seed} — so a conferencia do README foi feita")
        else:
            medido = contar_seed(seed)
            conferiu_seed = True
            print(f"seed: {seed}")
            print(f"  sha256 {medido['seed_sha256'][:8]}…{medido['seed_sha256'][-6:]}")
            for nome, valor in medido.items():
                if nome == "seed_sha256":
                    continue
                declarado = numero(manifesto.get(nome, "0"))
                estado = "ok" if declarado == valor else "DIVERGE"
                print(f"  {nome:<14} manifesto {formatar(declarado):>6} · seed {formatar(valor):>6}  {estado}")
                if declarado != valor:
                    problemas.append((ARQUIVO_MANIFESTO, 0))
                    achados, total = ocorrencias(raiz, declarado, nome)
                    for caminho, n in achados:
                        if anotacao:
                            print(f"::warning file={caminho},line={n},title=Total vencido::"
                                  f"cita {formatar(declarado)} {nome}, o seed tem {formatar(valor)}.")
                    if total:
                        print(f"  → {total} linha(s) de doc citam o total antigo"
                              f"{'' if total <= len(achados) else f' (mostrando {len(achados)})'}")
            if manifesto.get("seed_sha256", "").lower() != medido["seed_sha256"]:
                aviso(ARQUIVO_MANIFESTO, 1, "seed mudou",
                      f"sha256 do seed e {medido['seed_sha256'][:8]}…, manifesto declara "
                      f"{manifesto.get('seed_sha256', '?')[:8]}… — recontar e atualizar.")

    print()
    print(f"manifesto {ARQUIVO_MANIFESTO}: {len(manifesto)} declaracao(oes); "
          f"{len(problemas)} problema(s).")
    if not problemas:
        print("  README " + ("e seed batem" if conferiu_seed else "bate") + " com o manifesto")
    elif not conferiu_seed:
        print("  seed nao conferido nesta execucao (sem --seed e sem seed ao lado)")

    if args.estrito and problemas:
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
