#!/usr/bin/env python3
"""Compara dois fixtures de contrato (v2 × v3) e diz onde diverge.

Uso:
    python3 scripts/comparar-contrato.py docs/paridade/esperado.json novo.json

Saída: uma linha por rota, com PASS ou o diff. Sai com código 1 se houver
divergência em rota de **paridade estrita** (estrutura e contagem); divergência
nas rotas de **resultado de busca** é relatada e não quebra o processo, porque a
v3 usa trigrama e muda recall por decisão registrada (ver docs/paridade-contrato-v2.md).

Comparar um fixture com ele mesmo tem de dar PASS em tudo: é o autoteste.
"""

import json
import sys

# Rota → classe. estrita = tem de bater exatamente; resultado = relata e segue.
CLASSES = {
    "comarcas": "estrita",
    "foruns": "estrita",
    "unidades": "estrita",
    "contatos": "estrita",
    "hierarquia": "estrita",
    "contatos_q": "resultado",
    "busca": "resultado",
    "setores": "sem_equivalente",
}


def carrega(caminho):
    with open(caminho, encoding="utf-8") as fh:
        return json.load(fh)["rotas"]


def diff(a, b, caminho=""):
    """Diff recursivo e legível. Devolve lista de mensagens."""
    if type(a) is not type(b):
        return [f"{caminho}: tipo {type(a).__name__} × {type(b).__name__}"]
    if isinstance(a, dict):
        msgs = []
        for chave in sorted(set(a) | set(b)):
            if chave not in b:
                msgs.append(f"{caminho}.{chave}: só no esperado")
            elif chave not in a:
                msgs.append(f"{caminho}.{chave}: só no novo")
            else:
                msgs += diff(a[chave], b[chave], f"{caminho}.{chave}")
        return msgs
    if isinstance(a, list):
        if a == b:
            return []
        sobra_a = [x for x in a if x not in b]
        sobra_b = [x for x in b if x not in a]
        if not sobra_a and not sobra_b:
            return [f"{caminho}: mesmos itens, ordem diferente"]
        return [f"{caminho}: faltam no novo {sobra_a[:5]} / sobram {sobra_b[:5]}"]
    if a != b:
        return [f"{caminho}: {a!r} × {b!r}"]
    return []


def main():
    if len(sys.argv) < 3:
        print(__doc__)
        return 2
    esperado = carrega(sys.argv[1])
    novo = carrega(sys.argv[2])

    falhou = False
    for rota in sorted(set(esperado) | set(novo)):
        classe = CLASSES.get(rota, "estrita")
        if rota not in novo or rota not in esperado:
            print(f"{rota:<14} AUSENTE    ({'esperado' if rota in esperado else 'novo'} só de um lado)")
            falhou = falhou or classe == "estrita"
            continue
        msgs = diff(esperado[rota], novo[rota], rota)
        if not msgs:
            print(f"{rota:<14} PASS       {classe}")
            continue
        rotulo = {"estrita": "DIFERENTE", "resultado": "RECALL", "sem_equivalente": "IGNORADO"}[classe]
        print(f"{rota:<14} {rotulo:<10} {classe} — {len(msgs)} diferença(s)")
        for m in msgs[:8]:
            print(f"                {m}")
        if len(msgs) > 8:
            print(f"                … +{len(msgs) - 8}")
        if classe == "estrita":
            falhou = True

    print("\nRESULTADO:", "falhou" if falhou else "ok")
    return 1 if falhou else 0


if __name__ == "__main__":
    sys.exit(main())
