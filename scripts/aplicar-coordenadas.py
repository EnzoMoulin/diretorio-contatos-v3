#!/usr/bin/env python3
"""Reaplica no seed do wireframe as coordenadas geocodificadas dos 72 fóruns.

Por que este script existe: as coordenadas não vêm do seed do v2 (ele é congelado e nunca teve
lat/lon). Elas moram em `IDEIAS PARA O V3/wireframe/data/coordenadas.json`, e o seed do wireframe
só as recebe por um passe. Todo passe que reescreve `foruns` a partir do seed do v2 — hoje
`ordenar-e-completar.py` e `corrigir-unidades-achatadas.py` — apaga `lat`/`lon`/`geo_precisao`
sem avisar: o mapa continua funcionando, `montarPinos()` continua no `mapa.js`, e simplesmente
não há pino nenhum para desenhar (`Diretorio::locaisNoMapa()` devolve `[]`).

Uso:  python3 scripts/aplicar-coordenadas.py [--aplicar]
Sem --aplicar só mede. Rodar duas vezes seguidas não muda nada (idempotente).

Os dois scripts de migração passaram a chamar `preservar()` antes de gravar; este aqui é o
registro reproduzível do estado atual e a saída de emergência se alguém regravar o seed por fora.
"""
import json
import sys
from collections import Counter
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
V3 = RAIZ / "diretorio-contatos-v3" / "IDEIAS PARA O V3" / "wireframe" / "data" / "seed.json"
COORD = V3.parent / "coordenadas.json"


def preservar(foruns, coord):
    """Devolve cópias dos fóruns com lat/lon/geo_precisao — nunca muda o dict do seed do v2."""
    out = []
    for f in foruns:
        c = coord.get(f["id"])
        if not c:
            out.append(f)
            continue
        out.append({**f, "lat": c["lat"], "lon": c["lon"], "geo_precisao": c["precisao"]})
    return out


def main():
    aplicar = "--aplicar" in sys.argv
    coord = json.loads(COORD.read_text(encoding="utf-8"))
    seed = json.loads(V3.read_text(encoding="utf-8"))

    foruns = seed["foruns"]
    assert len(foruns) == 72, f"seed com {len(foruns)} fóruns, esperado 72"
    assert len(coord) == 72, f"coordenadas com {len(coord)} entradas, esperado 72"
    faltando = [f["id"] for f in foruns if f["id"] not in coord]
    assert not faltando, f"fóruns sem coordenada: {faltando}"

    novos = preservar(foruns, coord)
    mudou = [
        f["id"] for f, n in zip(foruns, novos)
        if (f.get("lat"), f.get("lon"), f.get("geo_precisao"))
        != (n.get("lat"), n.get("lon"), n.get("geo_precisao"))
    ]

    print(f"fóruns: {len(foruns)} · com coordenada: {len(coord)}")
    print("precisão:", dict(Counter(c["precisao"] for c in coord.values())))
    print(f"a aplicar: {len(mudou)}" + (f" ({', '.join(mudou[:6])}{'…' if len(mudou) > 6 else ''})" if mudou else " — já aplicado"))

    if not mudou:
        return 0

    # contagens que não podem mudar neste passe
    antes = (len(seed["nos"]), len(seed["contatos"]), len(seed["comarcas"]))
    assert antes == (856, 1807, 70), f"base fora do esperado: {antes}"

    if not aplicar:
        print("\n(dry-run) nada escrito; use --aplicar")
        return 0

    seed["foruns"] = novos
    V3.write_text(json.dumps(seed, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")
    print("\nescrito: seed do wireframe com lat/lon/geo_precisao nos 72 fóruns")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
