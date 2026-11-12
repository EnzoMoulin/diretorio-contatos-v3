#!/usr/bin/env python3
"""Captura o contrato de leitura do v2 como fixture de paridade — chaves, contagens e ordem.

Por que não guarda `id`: os identificadores do v2 são internos e descartáveis
(`M1`, `F1`, gerados pelo carregador), enquanto a v3 usa a chave lógica do seed
(`MUN0001`, `FOR0001`). Comparar por `id` entre versões não diz nada. O fixture
compara por **nome e ordem** (estrutura) e por **contagem** (contatos), que é o
que o usuário enxerga.

O arquivo não guarda telefone, e-mail nem nome de pessoa: os nomes de comarca,
fórum e unidade são públicos (ver `dados/README.md`), e a amostra de contatos usa
só `nome`/`cargo` já mascarados no seed (`NN`).

Uso:
    python3 scripts/capturar-contrato.py [http://127.0.0.1:8000] [saida.json]
"""

import json
import sys
import urllib.parse
import urllib.request

BASE = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8000"
SAIDA = sys.argv[2] if len(sys.argv) > 2 else "docs/paridade/esperado.json"

CONSULTAS = [
    "cartorio", "cartório", "vitoria", "vitória", "protocolo", "distribuicao",
    "civel", "juizado", "firma", "vara do trabalho", "sede", "imobiliario",
    "273134", "a", "  ", "", "cartorio de registro", "atendimento",
]


def pega(caminho, **params):
    url = f"{BASE.rstrip('/')}{caminho}"
    if params:
        url += "?" + urllib.parse.urlencode(params)
    with urllib.request.urlopen(url, timeout=30) as r:
        return json.loads(r.read().decode("utf-8"))


def nomes(lista):
    return [x.get("nome") for x in lista]


def main():
    saida = {"fonte": BASE, "rotas": {}, "notas": {}}

    comarcas = pega("/api/comarcas")
    saida["rotas"]["comarcas"] = {
        "total": len(comarcas),
        "ordem": nomes(comarcas),
        "por_comarca": {c["nome"]: {"total": c["total"], "n_foruns": c["n_foruns"],
                                    "n_unidades": c["n_unidades"], "n_setores": c["n_setores"]}
                        for c in comarcas},
    }

    amostra = [c["nome"] for c in comarcas][:6]
    mapa_comarca = {c["nome"]: c["id"] for c in comarcas}
    saida["rotas"]["foruns"] = {}
    for nome_comarca in amostra:
        foruns = pega("/api/foruns", comarca_id=mapa_comarca[nome_comarca])
        saida["rotas"]["foruns"][nome_comarca] = {
            "ordem": nomes(foruns),
            "totais": {f["nome"]: f["total"] for f in foruns},
            "n_unidades": {f["nome"]: f["n_unidades"] for f in foruns},
        }

    mapa_forum = {}
    for nome_comarca in amostra:
        foruns = pega("/api/foruns", comarca_id=mapa_comarca[nome_comarca])
        for f in foruns:
            mapa_forum[f["nome"]] = f["id"]
    amostra_foruns = list(mapa_forum)[:6]

    saida["rotas"]["unidades"] = {}
    for nome_forum in amostra_foruns:
        unidades = pega("/api/unidades", forum_id=mapa_forum[nome_forum])
        saida["rotas"]["unidades"][nome_forum] = {
            "ordem": nomes(unidades),
            "totais": {u["nome"]: u["total"] for u in unidades},
        }

    mapa_unidade = {}
    for nome_forum in amostra_foruns:
        for u in pega("/api/unidades", forum_id=mapa_forum[nome_forum]):
            mapa_unidade[u["nome"]] = u["id"]
    amostra_unidades = list(mapa_unidade)[:6]

    # O v2 separa `unidades` e `setores`; a v3 tem uma árvore única de `nos`.
    # A rota fica capturada para registro, mas não tem equivalente direto.
    saida["notas"]["setores"] = ("v2 tem unidades + setores; a v3 tem uma árvore única de nos. "
                                 "Contagem de setores não é comparável — compare contatos e locais.")
    saida["rotas"]["setores"] = {}
    for nome_unidade in amostra_unidades:
        setores = pega("/api/setores", unidade_id=mapa_unidade[nome_unidade])
        saida["rotas"]["setores"][nome_unidade] = {
            "total": len(setores),
            "nomes": nomes(setores),
        }

    filtros = [(("comarca_id"), n, mapa_comarca[n]) for n in amostra[:3]]
    filtros += [(("forum_id"), n, mapa_forum[n]) for n in amostra_foruns[:2]]
    filtros += [(("unidade_id"), n, mapa_unidade[n]) for n in amostra_unidades[:2]]
    saida["rotas"]["contatos"] = {}
    for campo, rotulo, valor in filtros:
        lista = pega("/api/contatos", **{campo: valor})
        saida["rotas"]["contatos"][f"{campo}={rotulo}"] = {"total": len(lista)}

    saida["rotas"]["contatos_q"] = {}
    for q in CONSULTAS:
        saida["rotas"]["contatos_q"][q] = {"total": len(pega("/api/contatos", q=q))}

    saida["rotas"]["busca"] = {}
    for q in CONSULTAS:
        if not q.strip():
            continue
        saida["rotas"]["busca"][q] = {"total": len(pega("/api/busca", q=q, limite=20))}

    saida["rotas"]["hierarquia"] = resumo_hierarquia(pega("/api/hierarquia"))

    amostra_contatos = pega("/api/contatos", comarca_id=mapa_comarca[amostra[0]])[:10]
    saida["amostra_contatos"] = [
        {k: c.get(k) for k in ("nome", "cargo", "descricao", "fonte",
                               "setor", "unidade", "forum")}
        for c in amostra_contatos
    ]

    with open(SAIDA, "w", encoding="utf-8") as fh:
        json.dump(saida, fh, ensure_ascii=False, indent=1, sort_keys=True)
    print(f"escrito {SAIDA}")


def resumo_hierarquia(hier):
    """Contagens da árvore, sem dado de contato.

    A árvore do v2 tem dois pontos de entrada por comarca: `nos` (a Sede, sem
    fórum) e `foruns[].nos`. Contar só o primeiro perde as unidades dos fóruns —
    foi o que produziu 249 em vez de 299 na primeira captura.
    """
    if isinstance(hier, list):
        locais = hier
    else:
        locais = hier.get("locais") or hier.get("comarcas") or hier.get("orgaos") or []

    def conta_nos(nos):
        total = 0
        for no in nos:
            total += 1
            total += conta_nos(no.get("nos") or no.get("filhos") or [])
        return total

    total_nos = 0
    total_foruns = 0
    for local in locais:
        total_nos += conta_nos(local.get("nos") or [])
        foruns = local.get("foruns") or []
        total_foruns += len(foruns)
        for forum in foruns:
            total_nos += conta_nos(forum.get("nos") or [])

    saida = {
        "locais": len(locais),
        "ordem": nomes(locais),
        "nos_total": total_nos,
        "foruns_total": total_foruns,
    }
    if isinstance(hier, dict) and "uteis" in hier:
        saida["uteis"] = len(hier["uteis"])
    return saida


if __name__ == "__main__":
    main()
