#!/usr/bin/env python3
"""Recoloca sob as varas os contatos que o export deixou no nível do fórum.

Cinco fóruns do catálogo de junho/2026 têm cabeçalho de unidade (vara, ofício,
cartório) e chegaram na base com `nos` vazio e todos os contatos em `uo=''`:
Itarana, Itapemirim, Alegre, Pancas e Alfredo Chaves. O contato não se perde — ele só está no
lugar errado, e a página do fórum repete "Cartório" dez vezes.

Barra de São Francisco tem cabeçalho de vara no catálogo, mas cada contato já
nomeia a vara no próprio rótulo ("Cartório da 1ª Vara Cível"): não é
achatamento, e por isso fica de fora.

O que este script faz, numa passada:
  1. cria 14 nós novos (UNI0839…UNI0856) e aponta os contatos para eles;
  2. mascara os três nomes de pessoa que sobraram no rótulo do contato
     (Comissariado (Maria Alice • Creuza) / (Fernando), em Itapemirim);
  3. escreve nos dois seeds e nos dois CSVs, e confere que os dois seeds
     continuam idênticos.

Uso:  python3 scripts/corrigir-unidades-achatadas.py [--aplicar]
Sem --aplicar só mostra o plano.
"""
import csv
import json
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]          # ~/Emprego/Projetos
V2 = RAIZ / "diretorio-contatos-v2" / "seed_mascarado.json"
V3 = RAIZ / "diretorio-contatos-v3" / "IDEIAS PARA O V3" / "wireframe" / "data" / "seed.json"
CSV_UNI = RAIZ / "dados" / "mascarados-oficial" / "UNIDADES_ORGANIZACIONAIS.csv"
CSV_CON = RAIZ / "dados" / "mascarados-oficial" / "CONTATOS.csv"

# (fórum, nome da unidade, [ids dos contatos], na ordem do catálogo)
PLANO = [
    ("FOR0012", "Vara do 2º Ofício", ["C000158"]),
    ("FOR0012", "Vara do 3º Ofício", ["C000162"]),
    ("FOR0025", "2ª Vara Cível e Vara da Fazenda Pública Municipal",
     ["C000252", "C000256", "C000257", "C000258"]),
    ("FOR0025", "2ª Vara Criminal",
     ["C000259", "C000260", "C000261", "C000262", "C000263", "C000264"]),
    ("FOR0025", "2ª Vara de Família, Vara da Infância e da Juventude e Vara de Órfão e Sucessões",
     ["C000265", "C000266", "C000267", "C000268", "C000269",
      "C000270", "C000271", "C000272"]),
    ("FOR0025", "2º Juizado Especial Cível, Juiz Especial Criminal e Vara da Fazenda Pública",
     ["C000273", "C000274", "C000275", "C000276", "C000277", "C000278"]),
    ("FOR0038", "Cartório Criminal", ["C000366", "C000367", "C000368"]),
    ("FOR0038", "Cartório Cível", ["C000369", "C000370", "C000371"]),
    ("FOR0038", "1ª Vara", ["C000372", "C000373", "C000374", "C000375"]),
    ("FOR0038", "2ª Vara", ["C000376", "C000377", "C000378", "C000379"]),
    ("FOR0056", "1ª Vara Cível", ["C000488", "C000490"]),
    ("FOR0056", "2ª Vara Criminal", ["C000491", "C000492"]),
    ("FOR0022", "Vara Cível", ["C000228"]),
    ("FOR0022", "Vara Criminal", ["C000229"]),
]

# rótulo atual -> rótulo mascarado
NOMES = {
    "Comissariado (Maria Alice • Creuza)": "Comissariado 01",
    "Comissariado (Fernando)": "Comissariado 02",
}


def main(aplicar: bool) -> int:
    s2 = json.loads(V2.read_text(encoding="utf-8"))
    s3 = json.loads(V3.read_text(encoding="utf-8"))

    nos = {n["id"]: n for n in s2["nos"]}
    contatos = {c["id"]: c for c in s2["contatos"]}
    assert len(nos) == 838, len(nos)
    assert len(contatos) == 1807, len(contatos)

    # 1. nós novos
    proximo = max(int(n["id"][3:]) for n in s2["nos"]) + 1
    novos, por_forum = [], {}
    for forum, nome, ids in PLANO:
        nid = f"UNI{proximo:04d}"
        proximo += 1
        ordem = por_forum.get(forum, 0) + 1
        por_forum[forum] = ordem
        nó = {"id": nid, "forum": forum, "pai": "", "tipo": "UNIDADE", "nome": nome,
              "end": "", "cep": "", "obs": "", "sel": True, "ativo": True,
              "ordem": float(ordem), "comarca": "", "idx": len(s2["nos"]) + len(novos)}
        novos.append((nó, ids))
        for cid in ids:
            c = contatos[cid]
            assert c["forum"] == forum, (cid, c["forum"], forum)
            assert c["uo"] == "", (cid, c["uo"])
            c["uo"] = nid

    # 2. nomes
    mascarados = 0
    for c in s2["contatos"]:
        if c["desc"] in NOMES:
            c["desc"] = NOMES[c["desc"]]
            mascarados += 1

    # 3. espelha nos dois seeds
    s2["nos"].extend(n for n, _ in novos)
    s3["nos"] = s2["nos"]
    s3["contatos"] = s2["contatos"]

    print(f"nós novos: {len(novos)}  ({novos[0][0]['id']}…{novos[-1][0]['id']})")
    print(f"contatos recolocados: {sum(len(i) for _, i in novos)}")
    print(f"rótulos mascarados: {mascarados}")
    print(f"nós: {len(s2['nos'])}   1º nível: {sum(1 for n in s2['nos'] if not n['pai'])}")
    for forum in ("FOR0012", "FOR0025", "FOR0038", "FOR0056", "FOR0022"):
        f = [n for n, _ in novos if n["forum"] == forum]
        print(f"  {forum}: {len(f)} unidades — " + " | ".join(n["nome"][:45] for n in f))

    if not aplicar:
        print("\n(dry-run; use --aplicar)")
        return 0

    V2.write_text(json.dumps(s2, ensure_ascii=False), encoding="utf-8")
    V3.write_text(json.dumps(s3, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")

    # 4. CSVs
    linhas = list(csv.DictReader(CSV_UNI.open(encoding="utf-8")))
    campos_uni = list(linhas[0].keys())
    with CSV_UNI.open("w", encoding="utf-8", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=campos_uni, quoting=csv.QUOTE_MINIMAL)
        w.writeheader()
        w.writerows(linhas)
        for n, _ in novos:
            w.writerow({"ID": n["id"], "FORUM_ID": n["forum"], "PAI_ID": "", "TIPO": n["tipo"],
                        "NOME": n["nome"], "ENDERECO": "", "CEP": "", "OBSERVACAO": "",
                        "SELECIONAVEL_ACESSO": "TRUE", "ATIVO": "TRUE",
                        "ORDEM": int(n["ordem"]), "COMARCA_ID": ""})

    linhas = list(csv.DictReader(CSV_CON.open(encoding="utf-8")))
    campos_con = list(linhas[0].keys())
    for linha in linhas:
        c = contatos[linha["ID"]]
        linha["UNIDADE_ORGANIZACIONAL_ID"] = c["uo"]
        linha["DESCRICAO"] = c["desc"]
    with CSV_CON.open("w", encoding="utf-8", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=campos_con, quoting=csv.QUOTE_ALL)
        w.writeheader()
        w.writerows(linhas)

    print("\nescrito: seed v2, seed v3, UNIDADES_ORGANIZACIONAIS.csv, CONTATOS.csv")
    return 0


if __name__ == "__main__":
    sys.exit(main("--aplicar" in sys.argv))
