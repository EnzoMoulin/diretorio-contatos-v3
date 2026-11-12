#!/usr/bin/env python3
"""Reconciliação da base do diretório contra os catálogos oficiais.

Confronta os CSVs de `dados/mascarados-oficial/` com os três documentos que
originaram a base:

  1. catálogo de ramais por comarcas (versão JUNHO/2026) — comarcas, fóruns,
     unidades/setores e e-mails;
  2. catálogo de ramais da Sede (agosto/2026) — unidades e setores da Sede;
  3. ofício de março/2024 (processo administrativo interno) — varas de Vitória.

A comparação é por **cobertura de palavras**, não por substring: no PDF o rótulo
e o telefone ficam em subcolunas, então uma etiqueta longa aparece cortada por
números no meio. Cobertura tolera o corte; substring não.

Requisito: poppler-utils (`pdftotext`).

Uso:
    python3 scripts/reconciliar-catalogo.py \
        --base ../dados/mascarados-oficial \
        --comarcas ../dados/catalogo-2026/comarcas.pdf \
        --sede ../dados/catalogo-2026/sede.pdf \
        --oficio ../dados/catalogo-2026/oficio-432-2024.pdf \
        --saida /tmp/reconciliacao.json

O JSON de saída contém nomes de unidades e contagens; não contém telefone,
e-mail nem nome de pessoa.
"""
import argparse
import csv
import html
import json
import re
import subprocess
import sys
import unicodedata
from collections import Counter
from pathlib import Path

SPLIT_X = 420.0       # divisória das duas colunas da página do catálogo de comarcas
LIMIAR_OK = 0.9       # cobertura a partir daqui: o catálogo sustenta o nome
LIMIAR_PARCIAL = 0.6  # entre parcial e ok: diferença de redação, não de dado


# --------------------------------------------------------------- normalização
def norm(s):
    s = unicodedata.normalize("NFKD", str(s or ""))
    s = "".join(c for c in s if not unicodedata.combining(c))
    for a, b in (("•", " "), ("–", "-"), ("—", "-"), ("nº", "no"), ("ª", "a"),
                 ("º", "o"), ("°", "o")):
        s = s.replace(a, b)
    s = re.sub(r"[^a-z0-9 ]", " ", s.lower())
    return re.sub(r"\s+", " ", s).strip()


def vocabulario(texto):
    return {t for t in norm(texto).split() if len(t) > 1}


def cobertura(nome, voc):
    alvo = [t for t in norm(nome).split() if len(t) > 1]
    if not alvo:
        return 1.0
    return sum(1 for t in alvo if t in voc) / len(alvo)


def classifica(nome, voc):
    c = cobertura(nome, voc)
    if c >= LIMIAR_OK:
        return "ok", c
    if c >= LIMIAR_PARCIAL:
        return "parcial", c
    return "ausente", c


# ------------------------------------------------------- leitura do PDF (linhas)
def paginas(pdf):
    xml = subprocess.run(["pdftotext", "-bbox", pdf, "-"],
                         capture_output=True, text=True, check=True).stdout
    saida = []
    for m in re.finditer(r'<page width="([\d.]+)" height="([\d.]+)">(.*?)</page>', xml, re.S):
        palavras = [(float(w.group(2)), float(w.group(1)), html.unescape(w.group(3)))
                    for w in re.finditer(
                        r'<word xMin="([\d.]+)" yMin="([\d.]+)" xMax="[\d.]+" yMax="[\d.]+">(.*?)</word>',
                        m.group(3))]
        saida.append(palavras)
    return saida


def colunas(palavras):
    baldes = ([], [])
    for y0, x0, texto in palavras:
        baldes[0 if x0 < SPLIT_X else 1].append((y0, x0, texto))
    resultado = []
    for balde in baldes:
        linhas = {}
        for y0, x0, texto in balde:
            linhas.setdefault(round(y0 / 3), []).append((x0, texto))
        resultado.append([(k * 3, " ".join(t for _, t in sorted(v))) for k, v in sorted(linhas.items())])
    return resultado


# ------------------------------------------- catálogo de comarcas (JUNHO/2026)
def extrai_comarcas(pdf):
    """comarca -> {locais, emails, texto, micro}. Regras descritas em docs/."""
    comarcas = {}
    atual = None
    micro = None
    for numero_pagina, palavras in enumerate(paginas(pdf), start=1):
        for numero_coluna, linhas in enumerate(colunas(palavras), start=1):
            textos = [t for _, t in linhas]
            if textos:
                rodape = textos[-1].strip()
                achado = re.match(r"^(?:\d{1,2}\s*)?([A-ZÀ-Ÿ\- ]{4,}):\s*(.+?)(?:\s*\d{1,2})?$", rodape)
                if achado:
                    micro = achado.group(1).strip()
            for pos, texto in enumerate(textos):
                limpo = texto.strip()
                proximo = textos[pos + 1].strip() if pos + 1 < len(textos) else ""
                junto = re.match(r"^([A-ZÀ-Ÿ][A-ZÀ-Ÿ .'\-]{2,40}?)\s*-\s*(F[OÓ]RUM\b.*)$", limpo)
                if junto:
                    nome = junto.group(1).strip().title()
                    atual = nome
                    registro = comarcas.setdefault(nome, {"paginas": [], "locais": [], "emails": [],
                                                          "texto": "", "micro": micro})
                    registro["locais"].append(re.sub(r"^(Fórum|Forum)\s+", "", junto.group(2)).strip())
                    continue
                if (re.match(r"^[A-ZÀ-Ÿ][A-ZÀ-Ÿ .\-'ºª]{2,45}$", limpo)
                        and proximo.lower().startswith(("forum", "fórum"))):
                    nome = (limpo.title().replace(" De ", " de ").replace(" Do ", " do ")
                            .replace(" Da ", " da ").replace(" Dos ", " dos ").replace(" Das ", " das "))
                    if len(norm(nome)) < 4 or norm(nome) in {"anexo", "anexos", "indice geral"}:
                        continue
                    atual = nome
                    comarcas.setdefault(nome, {"paginas": [], "locais": [], "emails": [],
                                               "texto": "", "micro": micro})
                    continue
                if atual is None:
                    continue
                registro = comarcas[atual]
                registro["texto"] += " " + norm(limpo)
                registro["emails"] += re.findall(r"[\w.\-]+@[\w.\-]+", limpo)
                if re.match(r"^(Fórum|Forum)\b", limpo):
                    registro["locais"].append(re.sub(r"^(Fórum|Forum)\s+", "", limpo).strip())
            if atual:
                pagina = f"{numero_pagina}/{numero_coluna}"
                if pagina not in comarcas[atual]["paginas"]:
                    comarcas[atual]["paginas"].append(pagina)
    for registro in comarcas.values():
        registro["emails"] = sorted({e.lower() for e in registro["emails"]})
        registro["locais"] = sorted(set(registro["locais"]))
        registro["texto"] = re.sub(r"\s+", " ", registro["texto"]).strip()
    return comarcas


def texto_de(pdf):
    return subprocess.run(["pdftotext", "-layout", pdf, "-"],
                          capture_output=True, text=True, check=True).stdout


# ------------------------------------------------------------------ comparação
def carrega_csv(pasta, nome):
    with open(Path(pasta) / f"{nome}.csv", encoding="utf-8") as fh:
        return list(csv.DictReader(fh))


def reconcilia(pasta, pdf_comarcas, pdf_sede, pdf_oficio):
    comarcas = carrega_csv(pasta, "COMARCAS")
    foruns = carrega_csv(pasta, "FORUM")
    nos = carrega_csv(pasta, "UNIDADES_ORGANIZACIONAIS")
    contatos = carrega_csv(pasta, "CONTATOS")
    uteis = carrega_csv(pasta, "TELEFONES_UTEIS")

    oficial = extrai_comarcas(pdf_comarcas)
    texto_sede = texto_de(pdf_sede)
    texto_oficio = texto_de(pdf_oficio)

    voc_comarca = {norm(n): vocabulario(r["texto"]) for n, r in oficial.items() if len(norm(n)) > 3}
    voc_global = set().union(*voc_comarca.values()) if voc_comarca else set()
    voc_sede = vocabulario(texto_sede)

    nome_com = {c["ID"]: c["NOME"] for c in comarcas}
    com_de_forum = {f["ID"]: f["COMARCA_ID"] for f in foruns}
    orgaos = {c["ID"] for c in comarcas if c["TIPO"] == "ORGAO"}

    rel = {"fonte": {"comarcas": str(pdf_comarcas), "sede": str(pdf_sede), "oficio": str(pdf_oficio)},
           "base": {"comarcas": len(comarcas), "foruns": len(foruns), "nos": len(nos),
                    "contatos": len(contatos), "uteis": len(uteis),
                    "contatos_por_tipo": dict(Counter(c["TIPO"] for c in contatos)),
                    "nos_por_tipo": dict(Counter(n["TIPO"] for n in nos))}}

    # A. comarcas
    base_com = {norm(c["NOME"]): c["NOME"] for c in comarcas if c["TIPO"] != "ORGAO"}
    cat_com = {norm(n): n for n in oficial if len(norm(n)) > 3 and norm(n) not in {"anexo", "anexos"}}
    rel["comarcas"] = {"no_catalogo": len(cat_com), "na_base": len(base_com),
                       "faltam_na_base": sorted(cat_com[k] for k in set(cat_com) - set(base_com)),
                       "sobram_na_base": sorted(base_com[k] for k in set(base_com) - set(cat_com))}

    # B. fóruns
    fora_forum, parcial_forum = [], []
    for f in foruns:
        com = f["COMARCA_ID"] or com_de_forum.get(f["ID"], "")
        estado, c = classifica(f["NOME"], voc_comarca.get(norm(nome_com.get(com, "")), set()))
        if estado == "ok":
            continue
        item = {"forum": f["NOME"], "comarca": nome_com.get(com, ""), "cobertura": round(c, 2)}
        (fora_forum if estado == "ausente" else parcial_forum).append(item)
    rel["foruns"] = {"total": len(foruns), "ausentes": fora_forum, "parciais": parcial_forum}

    # C. nós de comarca
    ausentes, parciais, deslocados = [], [], []
    for n in nos:
        com = n["COMARCA_ID"] or com_de_forum.get(n["FORUM_ID"], "")
        if com in orgaos:                      # Sede é conferida no item E
            continue
        estado, c = classifica(n["NOME"], voc_comarca.get(norm(nome_com.get(com, "")), set()))
        if estado == "ok":
            continue
        item = {"no": n["NOME"], "tipo": n["TIPO"], "comarca": nome_com.get(com, ""),
                "cobertura": round(c, 2)}
        if estado == "parcial":
            parciais.append(item)
        elif cobertura(n["NOME"], voc_global) >= LIMIAR_OK:
            deslocados.append(item)
        else:
            ausentes.append(item)
    rel["nos"] = {"conferidos": len(nos) - sum(1 for n in nos if
                                               (n["COMARCA_ID"] or com_de_forum.get(n["FORUM_ID"])) in orgaos),
                  "ausentes": ausentes, "parciais": parciais, "fora_da_comarca": deslocados}

    # D. e-mails
    emails = [(c["ID"], c["VALOR"].split("@")[0].strip().lower(), nome_com.get(c["COMARCA_ID"], ""))
              for c in contatos if c["TIPO"] == "E-mail" and "@" in c["VALOR"]]
    do_catalogo = set()
    for registro in oficial.values():
        do_catalogo |= {e.split("@")[0].lower() for e in registro["emails"]}
    for texto in (texto_oficio, texto_sede):
        do_catalogo |= {e.split("@")[0].lower() for e in re.findall(r"[\w.\-]+@[\w.\-]+", texto)}
    na_base = {local for _, local, _ in emails}
    rel["emails"] = {
        "na_base": len(emails),
        "localizados": sum(1 for e in emails if e[1] in na_base and e[1] in do_catalogo),
        "fora_do_catalogo": [{"id": i, "comarca": com} for i, local, com in emails
                             if local not in do_catalogo],
        "no_catalogo_e_nao_na_base": sorted(e for e in do_catalogo - na_base if len(e) > 3),
    }

    # E. Sede
    sede_nos = [n for n in nos if (n["COMARCA_ID"] or com_de_forum.get(n["FORUM_ID"])) in orgaos]
    sede_fora, sede_parcial = [], []
    for n in sede_nos:
        estado, c = classifica(n["NOME"], voc_sede)
        if estado == "ok":
            continue
        item = {"no": n["NOME"], "tipo": n["TIPO"], "cobertura": round(c, 2)}
        (sede_fora if estado == "ausente" else sede_parcial).append(item)
    rel["sede"] = {"nos": len(sede_nos), "ausentes": sede_fora, "parciais": sede_parcial,
                   "contatos": sum(1 for c in contatos
                                   if (c["COMARCA_ID"] or com_de_forum.get(c["FORUM_ID"])) in orgaos)}

    # F. ofício de março/2024
    citados = sorted({e.split("@")[0].lower()
                      for e in re.findall(r"[\w.\-]+@[\w.\-]+", texto_oficio)})
    rel["oficio"] = {"emails_citados": len(citados),
                     "fora_da_base": [e for e in citados if e not in na_base],
                     "contatos_com_obs": sum(1 for c in contatos if "oficio 432" in norm(c["OBSERVACAO"]))}
    return rel


def resumo(rel):
    b = rel["base"]
    print(f"BASE: {b['comarcas']} comarcas · {b['foruns']} fóruns · {b['nos']} nós · "
          f"{b['contatos']} contatos · {b['uteis']} telefones úteis")
    print(f"      contatos: {b['contatos_por_tipo']}")
    c = rel["comarcas"]
    print(f"\nA. COMARCAS catálogo={c['no_catalogo']} base={c['na_base']} · "
          f"faltam={c['faltam_na_base'] or '—'} · sobram={c['sobram_na_base'] or '—'}")
    f = rel["foruns"]
    print(f"B. FÓRUNS {f['total']}: ausentes={len(f['ausentes'])} parciais={len(f['parciais'])}")
    for x in f["ausentes"] + f["parciais"]:
        print(f"     · {x['forum']} ({x['comarca']}) cob={x['cobertura']}")
    n = rel["nos"]
    print(f"C. NÓS {n['conferidos']}: ausentes={len(n['ausentes'])} parciais={len(n['parciais'])} "
          f"fora_da_comarca={len(n['fora_da_comarca'])}")
    for chave in ("ausentes", "parciais", "fora_da_comarca"):
        for x in n[chave]:
            print(f"     · {x['tipo']} {x['no']} ({x['comarca']}) cob={x['cobertura']}")
    e = rel["emails"]
    print(f"D. E-MAILS {e['na_base']}: localizados={e['localizados']} "
          f"fora_do_catalogo={len(e['fora_do_catalogo'])}")
    print(f"     no catálogo, fora da base: {e['no_catalogo_e_nao_na_base'] or '—'}")
    s = rel["sede"]
    print(f"E. SEDE nós={s['nos']} contatos={s['contatos']}: ausentes={len(s['ausentes'])} "
          f"parciais={len(s['parciais'])}")
    for chave in ("ausentes", "parciais"):
        for x in s[chave]:
            print(f"     · {x['tipo']} {x['no']} cob={x['cobertura']}")
    o = rel["oficio"]
    print(f"F. OFÍCIO citados={o['emails_citados']} fora_da_base={o['fora_da_base'] or '—'} "
          f"contatos={o['contatos_com_obs']}")


def main():
    ap = argparse.ArgumentParser(description="Reconciliação da base contra os catálogos oficiais.")
    ap.add_argument("--base", required=True, help="pasta dos CSVs (mascarados-oficial)")
    ap.add_argument("--comarcas", required=True, help="PDF do catálogo por comarcas")
    ap.add_argument("--sede", required=True, help="PDF do catálogo de ramais da Sede")
    ap.add_argument("--oficio", required=True, help="PDF do ofício de março/2024")
    ap.add_argument("--saida", default="reconciliacao.json")
    args = ap.parse_args()

    rel = reconcilia(args.base, args.comarcas, args.sede, args.oficio)
    with open(args.saida, "w", encoding="utf-8") as fh:
        json.dump(rel, fh, ensure_ascii=False, indent=1)
    resumo(rel)
    print(f"\nrelatório: {args.saida}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
