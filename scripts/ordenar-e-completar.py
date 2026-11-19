#!/usr/bin/env python3
"""Ordem do catálogo nos dados + as quatro unidades da Barra de São Francisco.

Duas coisas, um passe só:

1. **Ordem explícita.** O seed já está na ordem do catálogo (`ramais-comarcas-junho-2026.pdf`):
   as 69 comarcas seguem a divisão por microrregião (Metropolitana → Central → Sul → …) e os
   fóruns seguem a ordem de aparição dentro de cada comarca — conferido por posição no PDF,
   72 de 72 fóruns localizados e 0 comarcas com fóruns fora de ordem. O que faltava era o campo:
   o PHP ordenava os fóruns alfabeticamente (`usort` por `nome`) e "Fórum Criminal" vinha antes
   de "Fórum Cível" porque `í` (0xC3 0xAD) é maior que `r` em bytes. Este script grava `ordem`
   em `comarcas` e `foruns` para que a sequência venha do dado, não da posição no array.

2. **Barra de São Francisco** (`FOR0069`) é o único fórum ainda sem nenhuma unidade cujo
   catálogo separa varas. O bloco da página 38 tem 22 contatos: 9 telefones no fórum e 13 sob
   quatro cabeçalhos:

     "1ª Vara Cível, 2ª Vara Cível, 2ª Vara Cível, Vara Comercial, Vara de Acidentes de
      Trabalho, Vara da Fazenda Pública e do Meio Ambiente"  (o catálogo repete "2ª Vara Cível";
      o nome é reproduzido como está)
         Cartório da 1ª Vara Cível · Cartório da 2ª Vara Cível · Assessoria da 2ª Vara Cível
         Gabinete do Juiz da 1ª Vara Cível · Gabinete do Juiz da 3ª Vara Cível
         Sala de Audiências da 3ª Vara Cível
     "1ª Vara Criminal"                    Cartório · Gabinete do Juiz
     "2ª Vara Criminal – Execução Penal"   Cartório · Sala de Audiência
     "Juizado Especial Cível e Juizado Especial Criminal"
         Cartório · Gabinete do Juiz · Sala de Audiência

   Seis contatos já dizem a vara no rótulo e vão para a primeira unidade; os sete genéricos
   são atribuídos na ordem do catálogo (3 Cartórios, 2 Gabinetes, 2 Salas). Os 10 restantes
   (Institucional, Geral, Sala dos Conciliadores, Central de Apoio Multidisciplinar, Oficiais
   de Justiça, Protocolo • Central de Mandados, Portaria, Sala de Vigilância, Contadoria,
   Secretaria) ficam no fórum, como no catálogo.

   São Domingos do Norte e Alto Rio Novo **não** entram: as duas páginas listam tudo num bloco
   único, sem cabeçalho de vara (conferido linha a linha, pelo x de cada linha). O que parece
   repetição ali é rótulo com dois números ("Cartório (27) 0000-0000 • 0000-0000").

Uso: python3 scripts/ordenar-e-completar.py [--aplicar]
"""
import csv
import json
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
V2 = RAIZ / "diretorio-contatos-v2" / "seed_mascarado.json"
V3 = RAIZ / "diretorio-contatos-v3" / "IDEIAS PARA O V3" / "wireframe" / "data" / "seed.json"
CSV_UNI = RAIZ / "dados" / "mascarados-oficial" / "UNIDADES_ORGANIZACIONAIS.csv"
CSV_CON = RAIZ / "dados" / "mascarados-oficial" / "CONTATOS.csv"
APLICAR = "--aplicar" in sys.argv

BSFC = "FOR0069"
PLANO = [
    ("1ª Vara Cível, 2ª Vara Cível, 2ª Vara Cível, Vara Comercial, Vara de Acidentes de "
     "Trabalho, Vara da Fazenda Pública e do Meio Ambiente",
     ["C000615", "C000620", "C000624", "C000626", "C000627", "C000628"]),
    ("1ª Vara Criminal", ["C000616", "C000621"]),
    ("2ª Vara Criminal – Execução Penal", ["C000617", "C000622"]),
    ("Juizado Especial Cível e Juizado Especial Criminal", ["C000618", "C000623", "C000625"]),
]


def main():
    d = json.loads(V2.read_text(encoding="utf-8"))

    # Uma passada só: depois de aplicado, os contatos da BSF já têm `uo` e o
    # assert abaixo falharia — sinal de "já aplicado", não bug.
    if any(n["id"] == "UNI0853" for n in d["nos"]):
        print("já aplicado: UNI0853 existe; nada a fazer")
        return

    # Numeração global e crescente na ordem do catálogo (1…70 comarcas, 1…72
    # fóruns) — o PHP da UI ordena por este campo dentro de cada comarca.
    for i, c in enumerate(d["comarcas"], 1):
        c["ordem"] = float(i)
    for i, f in enumerate(d["foruns"], 1):
        f["ordem"] = float(i)

    seq = max(int(n["id"][3:]) for n in d["nos"] if n["id"].startswith("UNI")) + 1
    novos, movidos = [], []
    for nome, ids in PLANO:
        nid = f"UNI{seq:04d}"
        seq += 1
        novos.append({
            "id": nid, "forum": BSFC, "pai": "", "tipo": "UNIDADE", "nome": nome,
            "end": "", "cep": "", "obs": "", "sel": True, "ativo": True,
            "ordem": float(len(novos) + 1), "comarca": "", "idx": 0,
        })
        for cid in ids:
            c = next(x for x in d["contatos"] if x["id"] == cid)
            assert c["forum"] == BSFC and not c["uo"], (cid, c["forum"], c["uo"])
            c["uo"] = nid
            movidos.append(cid)
    d["nos"].extend(novos)
    for i, n in enumerate(d["nos"]):
        n["idx"] = i

    print(f"ordem gravada: {len(d['comarcas'])} comarcas, {len(d['foruns'])} fóruns")
    print(f"nós novos: {len(novos)} ({novos[0]['id']}…{novos[-1]['id']}) | contatos recolocados: {len(movidos)}")
    for n in novos:
        q = sum(1 for c in d["contatos"] if c["uo"] == n["id"])
        print(f"  {n['id']} {n['nome'][:64]} — {q} contatos")
    print(f"total: {len(d['nos'])} nós, {len(d['contatos'])} contatos")

    if not APLICAR:
        print("\n(dry-run) nada escrito; use --aplicar")
        return

    V2.write_text(json.dumps(d, ensure_ascii=False), encoding="utf-8")
    v3 = json.loads(V3.read_text(encoding="utf-8"))
    v3["comarcas"], v3["foruns"] = d["comarcas"], d["foruns"]
    v3["nos"], v3["contatos"] = d["nos"], d["contatos"]
    V3.write_text(json.dumps(v3, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")

    linhas = list(csv.DictReader(CSV_UNI.open(encoding="utf-8")))
    campos = list(linhas[0].keys())
    with CSV_UNI.open("w", encoding="utf-8", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=campos, quoting=csv.QUOTE_MINIMAL)
        w.writeheader()
        w.writerows(linhas)
        for n in novos:
            w.writerow({"ID": n["id"], "FORUM_ID": n["forum"], "PAI_ID": "", "TIPO": n["tipo"],
                        "NOME": n["nome"], "ENDERECO": "", "CEP": "", "OBSERVACAO": "",
                        "SELECIONAVEL_ACESSO": "TRUE", "ATIVO": "TRUE",
                        "ORDEM": int(n["ordem"]), "COMARCA_ID": ""})

    linhas = list(csv.DictReader(CSV_CON.open(encoding="utf-8")))
    campos = list(linhas[0].keys())
    porid = {c["id"]: c for c in d["contatos"]}
    for linha in linhas:
        c = porid[linha["ID"]]
        linha["UNIDADE_ORGANIZACIONAL_ID"] = c["uo"]
    with CSV_CON.open("w", encoding="utf-8", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=campos, quoting=csv.QUOTE_ALL)
        w.writeheader()
        w.writerows(linhas)

    print("\nescrito: seed v2, seed v3, UNIDADES_ORGANIZACIONAIS.csv, CONTATOS.csv")


if __name__ == "__main__":
    main()
