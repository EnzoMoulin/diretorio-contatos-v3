#!/usr/bin/env python3
"""Carrega o seed mascarado do v2 no schema do v3 (db_diretorio).

Não é o ingestão oficial do motor (esse é do FastAPI, com normalização e `fonte`
granular). É o teste de carga que prova o DDL contra os dados reais medidos:
69 comarcas, 72 fóruns + 1 Sede, 856 nós, 1.807 contatos.

Uso:
    python3 db/carregar-seed.py ../diretorio-contatos-v2/seed_mascarado.json | psql "$URL" -v ON_ERROR_STOP=1

O script emite SQL (INSERT) em stdout, numa única transação. Só stdlib.
"""
from __future__ import annotations

import json
import sys
from typing import Any

# Procedência documentada: só se preenche o que o seed diz de onde veio.
#   - "Catálogo de ramais — agosto/2026"  -> volume da Sede, 28/08/2026
#   - "Contato provisório — validar vigência." -> ofício da fonte (março de 2024)
#   - sem observação -> veio da base mascarada, sem documento declarado
FONTE_SEDE = "catalogo-sede-2026-08-28.pdf"
DATA_SEDE = "2026-08-28"
FONTE_OFICIO = "oficio-2024-03.pdf"
DATA_OFICIO = "2024-03-01"
FONTE_BASE = "seed (base mascarada do v2)"
DATA_BASE = "NULL"


def q(txt: Any) -> str:
    """Literal SQL escapado ('' para apóstrofo)."""
    if txt is None:
        return "NULL"
    return "'" + str(txt).replace("'", "''") + "'"


def procedencia(obs: str) -> tuple[str, str]:
    obs = (obs or "").strip().lower()
    if "agosto/2026" in obs:
        return FONTE_SEDE, q(DATA_SEDE)
    if "provis" in obs:
        return FONTE_OFICIO, q(DATA_OFICIO)
    return FONTE_BASE, DATA_BASE


def main() -> int:
    if len(sys.argv) < 2:
        print(__doc__, file=sys.stderr)
        return 2
    with open(sys.argv[1], encoding="utf-8") as fh:
        seed = json.load(fh)

    saida: list[str] = []
    add = saida.append
    add("BEGIN;")
    add("SET client_encoding = 'UTF8';")

    # ---- comarcas: MUN0000 (tipo ORGAO) não é comarca, é a Sede --------------
    sede = [c for c in seed["comarcas"] if c.get("tipo") == "ORGAO"]
    comarcas = [c for c in seed["comarcas"] if c.get("tipo") != "ORGAO"]
    if len(sede) != 1:
        print(f"esperava 1 linha tipo ORGAO nas comarcas, achei {len(sede)}", file=sys.stderr)
        return 1

    add("INSERT INTO comarcas (chave, nome, codigo_ibge, ordem_num, ordem_idx) VALUES")
    add(",\n".join(
        f"({q(c['id'])}, {q(c['nome'])}, {q(c.get('ibge', ''))}, "
        f"{c.get('ordem') if c.get('ordem') is not None else 'NULL'}, {c.get('idx', 0)})"
        for c in comarcas) + ";")

    # ---- locais: 72 fóruns + a Sede (mesmo nível; a Sede é o único órgão) ----
    add("INSERT INTO locais (chave, tipo, comarca_id, nome, endereco, cep, email, observacao, ordem_num, ordem_idx) VALUES")
    linhas = [
        f"( {q(f['id'])}, 'FORUM', (SELECT id FROM comarcas WHERE chave = {q(f['comarca_id'])}), "
        f"{q(f['nome'])}, {q(f.get('end', ''))}, {q(f.get('cep', ''))}, {q(f.get('email', ''))}, "
        f"{q(f.get('obs', ''))}, {f.get('ordem') if f.get('ordem') is not None else 'NULL'}, {f.get('idx', 0)})"
        for f in seed["foruns"]]
    s = sede[0]
    linhas.append(
        f"( {q(s['id'])}, 'SEDE', NULL, {q(s['nome'])}, {q(s.get('end', ''))}, "
        f"{q(s.get('cep', ''))}, {q(s.get('email', ''))}, {q(s.get('obs', ''))}, "
        f"{s.get('ordem') if s.get('ordem') is not None else 'NULL'}, {s.get('idx', 0)})")
    add(",\n".join(linhas) + ";")

    # ---- nós: duas passadas (pai antes do filho) ----------------------------
    nos = [n for n in seed["nos"] if n.get("ativo", True)]
    primeiro = [n for n in nos if not n.get("pai")]
    segundo = [n for n in nos if n.get("pai")]

    def local_de(no: dict) -> str:
        if no.get("forum"):
            return f"(SELECT id FROM locais WHERE chave = {q(no['forum'])})"
        return f"(SELECT id FROM locais WHERE chave = {q(s['id'])})"

    add("INSERT INTO nos (chave, origem, local_id, pai_id, tipo, nome, endereco, cep, observacao, selecionavel, ordem_num, ordem_idx) VALUES")
    add(",\n".join(
        f"({q(n['id'])}, 'seed', {local_de(n)}, NULL, {q(n.get('tipo') or 'UNIDADE')}, {q(n['nome'])}, "
        f"{q(n.get('end', ''))}, {q(n.get('cep', ''))}, {q(n.get('obs', ''))}, "
        f"{'true' if n.get('sel') else 'false'}, "
        f"{n.get('ordem') if n.get('ordem') is not None else 'NULL'}, {n.get('idx', 0)})"
        for n in primeiro) + ";")

    def pai_raiz(no: dict) -> dict:
        """Sobe até o ancestral de primeiro nível (o schema aceita 2 níveis)."""
        vistos: set[str] = set()
        atual = no
        por_id = {x["id"]: x for x in nos}
        while atual.get("pai") and atual["id"] not in vistos:
            vistos.add(atual["id"])
            atual = por_id.get(atual["pai"], atual)
        return atual

    add("INSERT INTO nos (chave, origem, local_id, pai_id, tipo, nome, endereco, cep, observacao, selecionavel, ordem_num, ordem_idx) VALUES")
    add(",\n".join(
        f"({q(n['id'])}, 'seed', {local_de(n)}, "
        f"(SELECT id FROM nos WHERE chave = {q(pai_raiz(n)['id'])}), "
        f"{q(n.get('tipo') or 'SETOR')}, {q(n['nome'])}, {q(n.get('end', ''))}, "
        f"{q(n.get('cep', ''))}, {q(n.get('obs', ''))}, "
        f"{'true' if n.get('sel') else 'false'}, "
        f"{n.get('ordem') if n.get('ordem') is not None else 'NULL'}, {n.get('idx', 0)})"
        for n in segundo) + ";")

    # ---- contatos -----------------------------------------------------------
    add("INSERT INTO contatos (chave, local_id, no_id, tipo, descricao, valor, ordem_num, ordem_idx, fonte, vigente_desde) VALUES")
    linhas = []
    for c in seed["contatos"]:
        # o local vem do próprio contato quando ele pertence ao local; senão do
        # nó (nó sabe seu local: é o que o v3 cobra e o v2 reconstruía por JOIN)
        if c.get("forum"):
            local = f"(SELECT id FROM locais WHERE chave = {q(c['forum'])})"
        elif c.get("uo"):
            local = f"(SELECT local_id FROM nos WHERE chave = {q(c['uo'])})"
        else:
            local = f"(SELECT id FROM locais WHERE chave = {q(s['id'])})"
        no_id = f"(SELECT id FROM nos WHERE chave = {q(c['uo'])})" if c.get("uo") else "NULL"
        fonte, data = procedencia(c.get("obs", ""))
        linhas.append(
            f"({q(c['id'])}, {local}, {no_id}, {q(c.get('tipo') or 'Telefone')}, "
            f"{q(c.get('desc', ''))}, {q(c['valor'])}, "
            f"{c.get('ordem') if c.get('ordem') is not None else 'NULL'}, {c.get('idx', 0)}, "
            f"{q(fonte)}, {data})")
    add(",\n".join(linhas) + ";")

    # ---- telefones úteis ----------------------------------------------------
    uteis = seed.get("uteis") or []
    if uteis:
        add("INSERT INTO telefones_uteis (chave, nome, tipo, valor, ordem_idx, fonte, vigente_desde) VALUES")
        add(",\n".join(
            f"({q(u['id'])}, {q(u['nome'])}, {q(u.get('tipo') or 'Telefone')}, {q(u['valor'])}, "
            f"{i}, {q(FONTE_BASE)}, {DATA_BASE})"
            for i, u in enumerate(uteis)) + ";")

    add("COMMIT;")
    print("\n".join(saida))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
