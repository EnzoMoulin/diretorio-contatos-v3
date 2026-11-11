#!/usr/bin/env python3
"""Varredura de privacidade: dado real nao entra no repositorio.

Versao de bolso do teste do v2 (`tests/test_sem_telefone_real.py`): roda em
qualquer arvore, sem servidor e sem banco, e serve para CI.

Uso:
    python3 scripts/varrer_privacidade.py            # varre o repo inteiro
    python3 scripts/varrer_privacidade.py docs/x.md  # varre um arquivo

Codigo de saida 1 se achar telefone ou e-mail fora do padrao sintetico -> e o
que faz o CI ficar vermelho antes de o repositorio ser publicado.

Padrao sintetico aceito: mascara com `X`; os blocos de exemplo `3333-00NN`,
WhatsApp `99999-000N`, placeholder `0000-0000`; os dominios `exemplo.jus.br`
e `empresa-exemplo.com.br`; e o que cada repo declarar em
`.privacidade-sinteticos` na raiz (uma linha por valor literal, ou `re:<regex>`,
`#` comenta) — use so para fixture de teste, nunca para calar dado real.

Marca institucional, referencia a documento interno e honorifico saem como
aviso, nao como falha: sao decisao de identidade do dono, nao vazamento.

Limite consciente: so le texto versionado. Planilha, PDF, dump e imagem ficam
de fora por extensao; esses se auditam na origem, com a mascara.
"""
import os
import re
import sys

PULAR_DIR = {".git", "node_modules", "__pycache__", "backups", "dist",
             ".venv", "vendor"}
PULAR_EXT = {".png", ".jpg", ".jpeg", ".gif", ".mp4", ".pdf", ".ico",
             ".woff", ".woff2", ".db", ".sqlite", ".zip", ".pyc", ".xlsx",
             ".ndjson", ".docx", ".pptx", ".otf", ".ttf", ".map"}

TELEFONE = re.compile(
    r"(?<![\w-])(?:\(?\d{2}\)?[\s.\-]?)?\d{4,5}[\s.\-]?\d{4}(?![\w-])")
EMAIL = re.compile(r"[\w.+-]+@[\w-]+(?:\.[\w-]+)+")
BRAND = re.compile(r"\bTJES\b|\bPJES\b|tjes\.jus\.br|Poder Judici[áa]rio|"
                   r"Tribunal de Justi[çc]a", re.I)
DOC_INTERNO = re.compile(r"\bSEI\b|SEI[_-]\d|Of[íi]cio\s*\d|n[º°]\s*\d{3,}/\d{4}")
NOME = re.compile(r"\b(?:Des\.|Desembargador[a]?|Comiss[áa]ri[oa])\s+"
                  r"[A-ZÁÉÍÓÚÂÊÔÃÕÇ][a-zá-ú]+")

# blocos de exemplo que o proprio projeto usa em seed, placeholder e docstring
SINTETICO_TEL = re.compile(
    r"(?:\(27\) )?(?:3333-00\d\d|99999-000\d|0000-0000)")
DOMINIOS_FICTICIOS = {"exemplo.jus.br", "empresa-exemplo.com.br",
                      "example.com", "exemplo.com"}
ARQUIVO_ALLOWLIST = ".privacidade-sinteticos"
ESTE_ARQUIVO = os.path.abspath(__file__)


def carregar_allowlist(raiz):
    """Valores sinteticos declarados pelo repo; literal ou `re:<regex>`."""
    exatos, regexes = set(), []
    caminho = os.path.join(raiz, ARQUIVO_ALLOWLIST)
    try:
        with open(caminho, encoding="utf-8") as f:
            linhas = f.read().splitlines()
    except OSError:
        return exatos, regexes
    for linha in linhas:
        linha = linha.strip()
        if not linha or linha.startswith("#"):
            continue
        if linha.startswith("re:"):
            regexes.append(re.compile(linha[3:].strip()))
        else:
            exatos.add(linha)
    return exatos, regexes


def listar_alvos(caminho):
    if os.path.isfile(caminho):
        return [caminho]
    alvos = []
    for base, dirs, arquivos in os.walk(caminho):
        dirs[:] = sorted(d for d in dirs if d not in PULAR_DIR)
        for nome in arquivos:
            if os.path.splitext(nome)[1].lower() in PULAR_EXT:
                continue
            alvos.append(os.path.join(base, nome))
    return sorted(alvos)


def varrer(caminho, raiz_repo):
    exatos, regexes = carregar_allowlist(raiz_repo)

    def sintetico(valor):
        if "X" in valor or SINTETICO_TEL.fullmatch(valor) or valor in exatos:
            return True
        return any(r.fullmatch(valor) for r in regexes)

    achados = {"telefone": [], "email": [], "marca": [], "doc": [], "nome": []}
    raiz = caminho if os.path.isdir(caminho) else os.path.dirname(caminho)
    for p in listar_alvos(caminho):
        if os.path.abspath(p) == ESTE_ARQUIVO:
            continue  # o proprio detector nao e dado
        try:
            with open(p, encoding="utf-8") as f:
                texto = f.read()
        except (OSError, UnicodeDecodeError):
            continue
        rel = os.path.relpath(p, raiz)
        for i, linha in enumerate(texto.splitlines(), 1):
            for m in TELEFONE.finditer(linha):
                valor = m.group().strip()
                if not sintetico(valor):
                    achados["telefone"].append(f"{rel}:{i}: {valor}")
            for m in EMAIL.finditer(linha):
                valor = m.group()
                dom = valor.split("@")[-1].lower()
                if dom in DOMINIOS_FICTICIOS or re.fullmatch(r"\d+\.\d+\.\d+", dom):
                    continue  # versao de pacote npm nao e e-mail
                achados["email"].append(f"{rel}:{i}: {valor}")
            if BRAND.search(linha):
                achados["marca"].append(f"{rel}:{i}: {linha.strip()[:110]}")
            for m in DOC_INTERNO.finditer(linha):
                achados["doc"].append(f"{rel}:{i}: {m.group()}")
            for m in NOME.finditer(linha):
                achados["nome"].append(f"{rel}:{i}: {m.group()}")
    for chave, itens in achados.items():
        print(f"\n## {chave}: {len(itens)}")
        for item in itens[:60]:
            print("  " + item)
        if len(itens) > 60:
            print(f"  ... +{len(itens) - 60}")
    return achados


def main():
    raiz_repo = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    if not os.path.isdir(os.path.join(raiz_repo, ".git")):
        raiz_repo = os.getcwd()
    total = {"telefone": 0, "email": 0}
    for alvo in (sys.argv[1:] or [raiz_repo]):
        print("=" * 70)
        print("ALVO:", alvo)
        resultado = varrer(alvo, raiz_repo)
        for chave in total:
            total[chave] += len(resultado[chave])
    dados = total["telefone"] + total["email"]
    print(f"\nDADO REAL (telefone+e-mail): {dados}")
    return 1 if dados else 0


if __name__ == "__main__":
    sys.exit(main())
