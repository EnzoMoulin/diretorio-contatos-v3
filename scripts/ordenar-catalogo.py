#!/usr/bin/env python3
"""Ordem do catálogo nos contatos: um rótulo do catálogo = um bloco de linhas.

O seed guarda `ordem` do contato com o sentido da coluna do catálogo (1 = primeira coluna,
2 = segunda…) e o array agrupa por unidade. Isso separa números que a página imprime na
mesma linha: no Fórum Criminal (pág. 6), "Secretaria de Gestão do Foro" tem seis números —
no seed, um fica entre os contatos do fórum e os outros cinco no fim do bloco, porque só
o primeiro está na coluna 1.

Comarcas e fóruns já estão na ordem do catálogo (1…70 e 1…72) — este passe não os toca. Os nós
já estão na ordem da página, mas a coluna `ORDEM` empata setor e vara no mesmo pai; o passe
renumera 1…n por pai. O que ele reordena de fato é `contatos`, por bloco, que é o bloco do
fórum (sem unidade) seguido de cada unidade:

1. o bloco do fórum vem antes das unidades, como na página;
2. dentro do bloco o rótulo manda: todos os números do mesmo rótulo ficam juntos, na
   posição em que o rótulo aparece primeiro (comparação sem caixa nem acento);
3. dentro do rótulo vale o `idx`, a ordem de leitura da página.

`idx` não muda — é a linha do catálogo. `ordem` passa a ser 1…n dentro do bloco, e o array
é gravado na ordem de leitura: comarca → fórum → bloco → contato.

Uso: python3 scripts/ordenar-catalogo.py [--aplicar]
"""

from __future__ import annotations

import json
import sys
import unicodedata
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
V2 = Path("/home/enzo/Emprego/Projetos/diretorio-contatos-v2/seed_mascarado.json")
V3 = RAIZ / "IDEIAS PARA O V3" / "wireframe" / "data" / "seed.json"

GUARDA = {"nos": 856, "contatos": 1807, "comarcas": 70, "foruns": 72}


def chave(rotulo: str) -> str:
    """Rótulo sem caixa, acento nem espaço duplicado — 'Correio • Mensageria' == 'correio • mensageria'."""
    s = unicodedata.normalize("NFKD", rotulo or "")
    s = "".join(c for c in s if not unicodedata.combining(c))
    return " ".join(s.casefold().split())


def ordenar_nos(seed: dict, pos_forum: dict) -> list[dict]:
    """Nós na ordem da página, com `ordem` 1…n por pai.

    A coluna `ORDEM` do CSV dá 1 ao setor de atendimento e 1 à primeira vara do mesmo pai — empate
    que qualquer ordenação futura desfaz. `idx` é a leitura da página, então ele manda; os filhos
    vêm logo depois do pai para a árvore poder ser renderizada em sequência.
    """
    grupos: dict[tuple, list[dict]] = {}
    for n in seed["nos"]:
        grupos.setdefault((n.get("forum", ""), n.get("pai", "")), []).append(n)

    for _, itens in grupos.items():
        itens.sort(key=lambda n: n.get("idx") or 0)
        for ordem, n in enumerate(itens, start=1):
            n["ordem"] = float(ordem)

    def posicao(n: dict) -> tuple:
        # profundidade primeiro só para o pai abrir o bloco; dentro do pai vale o idx
        return (pos_forum.get(n.get("forum", ""), 10**6), bool(n.get("pai")), n.get("idx") or 0)

    return sorted(seed["nos"], key=posicao)


def ordenar(seed: dict) -> tuple[list[dict], list[dict]]:
    foruns = sorted(seed["foruns"], key=lambda f: (f.get("ordem") or 0, f.get("idx") or 0))
    pos_forum = {f["id"]: i for i, f in enumerate(foruns)}
    nos = ordenar_nos(seed, pos_forum)

    # unidade → (posição do fórum, idx do nó), para os blocos saírem na ordem da página
    pos_no = {n["id"]: (pos_forum.get(n.get("forum", ""), 10**6), n.get("idx") or 0)
              for n in seed["nos"]}

    blocos: dict[tuple, list[dict]] = {}
    soltos: list[dict] = []
    for c in seed["contatos"]:
        if not c.get("forum"):
            soltos.append(c)
            continue
        blocos.setdefault((c["forum"], c.get("uo") or ""), []).append(c)

    saida: list[dict] = []
    for f in foruns:
        # bloco do fórum primeiro (uo vazio), depois as unidades na ordem dos nós
        chaves = [k for k in blocos if k[0] == f["id"]]
        chaves.sort(key=lambda k: (k[1] != "", pos_no.get(k[1], (10**6, 10**6))[1]))

        for k in chaves:
            linhas = sorted(blocos[k], key=lambda c: c.get("idx") or 0)
            vistos, bloco = set(), []
            for c in linhas:
                if c["id"] in vistos:
                    continue
                grupo = [o for o in linhas if chave(o.get("desc", "")) == chave(c.get("desc", ""))]
                vistos.update(o["id"] for o in grupo)
                bloco.extend(grupo)

            for ordem, c in enumerate(bloco, start=1):
                c["ordem"] = float(ordem)
            saida.extend(bloco)

    # contato sem fórum (Sede) fica no fim, na ordem em que estava
    return nos, saida + soltos


def conferir(seed: dict, contatos: list[dict]) -> list[str]:
    erros = []
    for campo in ("nos", "comarcas", "foruns"):
        if len(seed[campo]) != GUARDA[campo]:
            erros.append(f"{campo}: {len(seed[campo])} != {GUARDA[campo]}")
    if len(contatos) != GUARDA["contatos"]:
        erros.append(f"contatos: {len(contatos)} != {GUARDA['contatos']}")
    if len({c["id"] for c in contatos}) != len(contatos):
        erros.append("contatos com id repetido")
    return erros


def amostra(seed: dict, contatos: list[dict], fid: str = "FOR0002") -> None:
    """Os contatos do bloco do fórum, na ordem nova — é o caso que o Enzo apontou."""
    print(f"\n{fid} · bloco do fórum (sem unidade):")
    for c in contatos:
        if c.get("forum") == fid and not c.get("uo"):
            print(f"  {int(c['ordem']):>2}. {c['tipo']:8} {c['valor']:24} {c.get('desc')}")


def main() -> None:
    aplicar = "--aplicar" in sys.argv
    base = json.loads(V2.read_text(encoding="utf-8"))
    nos, contatos = ordenar(base)
    erros = conferir(base, contatos)
    if erros:
        print("GUARDA FALHOU:", *erros, sep="\n  ")
        raise SystemExit(1)

    amostra(base, contatos)

    if not aplicar:
        print("\n(dry-run) nada gravado — use --aplicar")
        return

    base["nos"] = nos
    base["contatos"] = contatos
    V2.write_text(json.dumps(base, ensure_ascii=False), encoding="utf-8")

    v3 = json.loads(V3.read_text(encoding="utf-8"))
    v3["nos"] = nos
    v3["contatos"] = contatos
    V3.write_text(json.dumps(v3, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")
    print(f"\ngravado: {V2.name} e {V3.name} · {len(nos)} nós, {len(contatos)} contatos")


if __name__ == "__main__":
    main()
