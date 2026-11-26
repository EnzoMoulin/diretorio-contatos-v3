#!/usr/bin/env python3
"""Compara o contrato de leitura do v2 com o da v3 — campo a campo, com diff legível.

Dois modos:

    # 1. dois fixtures já capturados (o autoteste é este modo com o mesmo arquivo)
    python3 scripts/comparar-contrato.py docs/paridade/esperado.json /tmp/v3.json

    # 2. a v3 no ar, capturando o que ela já expõe
    python3 scripts/comparar-contrato.py --v3 http://127.0.0.1:8000

O fixture do v2 (`docs/paridade/esperado.json`) é a referência. A v3 ainda não
tem todas as rotas de leitura, então cada rota tem uma **cobertura**:

    completa   a rota da v3 existe e devolve todos os campos do fixture
    parcial    a rota da v3 existe e devolve parte dos campos (ex.: `comarcas`
               empresta `ordem`/`total` de `/api/v1/hierarquia`)
    pendente   a rota da v3 ainda não existe — não falha, fica registrada
    sem_equivalente  o modelo mudou de propósito (setores)

Classe **estrita** falha em qualquer diferença de campo comparado. Classe
**resultado** relata a diferença e não falha: a busca da v3 usa trigrama,
sinônimos e normaliza acento por decisão registrada em `docs/paridade-contrato-v2.md`.

`--exigir-completo` transforma rota pendente em falha: é o modo que o CI usará
quando as rotas de leitura da v3 estiverem todas no ar.
"""

import argparse
import json
import sys
import urllib.parse
import urllib.request

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

# O que a v3 expõe hoje. `chaves` é o recorte do fixture que a v3 consegue responder;
# `None` significa a rota inteira. Manter isto ao lado de CLASSES evita que o
# comparador anuncie paridade que a v3 não tem.
COBERTURA_V3 = {
    "hierarquia": {"estado": "completa", "origem": "/api/v1/hierarquia"},
    "comarcas": {"estado": "parcial", "chaves": ["total", "ordem"],
                 "origem": "/api/v1/hierarquia"},
    "busca": {"estado": "parcial", "rotulo": "total por consulta",
              "origem": "/api/v1/busca?q=&limite=20"},
    "foruns": {"estado": "pendente", "motivo": "rota /api/v1/locais?comarca= ainda não existe"},
    "unidades": {"estado": "pendente", "motivo": "rota /api/v1/locais/{chave}/nos ainda não existe"},
    "contatos": {"estado": "pendente", "motivo": "rota /api/v1/contatos ainda não existe"},
    "contatos_q": {"estado": "pendente", "motivo": "rota /api/v1/contatos ainda não existe"},
    "setores": {"estado": "sem_equivalente", "motivo": "v2 separa unidades e setores; v3 tem árvore única de nós"},
}

CONSULTAS = [
    "cartorio", "cartório", "vitoria", "vitória", "protocolo", "distribuicao",
    "civel", "juizado", "firma", "vara do trabalho", "sede", "imobiliario",
    "273134", "a", "  ", "", "cartorio de registro", "atendimento",
]


def pega(base, caminho, **params):
    url = f"{base.rstrip('/')}{caminho}"
    if params:
        url += "?" + urllib.parse.urlencode(params)
    with urllib.request.urlopen(url, timeout=30) as r:
        return json.loads(r.read().decode("utf-8"))


def carrega(caminho):
    with open(caminho, encoding="utf-8") as fh:
        return json.load(fh)["rotas"]


def contar_campos(valor):
    """Folhas comparáveis de um recorte do fixture (para dizer a cobertura real)."""
    if isinstance(valor, dict):
        return sum(contar_campos(v) for v in valor.values())
    if isinstance(valor, list):
        return 1
    return 1


def recorte(rota, dados, cobertura):
    """Aplica a cobertura da v3 ao recorte do fixture."""
    chaves = cobertura.get("chaves")
    if chaves is None:
        return dados
    if not isinstance(dados, dict):
        return dados
    return {k: dados[k] for k in chaves if k in dados}


def diff(a, b, caminho=""):
    """Diff recursivo e legível entre dois recortes completos."""
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


def diff_parcial(esperado, novo, caminho=""):
    """Diff que só percorre o que o novo recorte tem — o resto é pendência, não erro."""
    if isinstance(novo, dict) and isinstance(esperado, dict):
        msgs = []
        for chave in sorted(novo):
            if chave not in esperado:
                msgs.append(f"{caminho}.{chave}: só no novo")
            else:
                msgs += diff_parcial(esperado[chave], novo[chave], f"{caminho}.{chave}")
        return msgs
    return diff(esperado, novo, caminho)


def capturar_v3(base):
    """O contrato que a v3 consegue responder hoje, no formato do fixture do v2."""
    hier = pega(base, "/api/v1/hierarquia")
    novo = {
        "hierarquia": {k: hier[k] for k in
                       ("foruns_total", "locais", "nos_total", "ordem", "uteis")
                       if k in hier},
        # `/api/v1/locais` ainda não existe; a ordem das comarcas já está na árvore.
        "comarcas": {"total": len(hier.get("ordem", [])), "ordem": list(hier.get("ordem", []))},
        "busca": {},
    }
    for q in CONSULTAS:
        if not q.strip():
            continue
        novo["busca"][q] = {"total": pega(base, "/api/v1/busca", q=q, limite=20)["total"]}
    return novo


def comparar(esperado, novo, exigir_completo, origem, modo_v3):
    falhou = False
    for rota in sorted(set(esperado) | set(novo) | set(CLASSES)):
        classe = CLASSES.get(rota, "estrita")
        cobertura = COBERTURA_V3.get(rota) if modo_v3 else None

        if classe == "sem_equivalente":
            motivo = (cobertura or {}).get("motivo", "modelo mudou")
            print(f"{rota:<14} IGNORADO   sem equivalente — {motivo}")
            continue

        if rota not in novo:
            if modo_v3:
                motivo = (cobertura or {}).get("motivo", "rota da v3 ausente")
                print(f"{rota:<14} PENDENTE   {classe} — {motivo}")
                falhou = falhou or exigir_completo
            else:
                print(f"{rota:<14} AUSENTE    {classe} — só no esperado")
                falhou = falhou or classe == "estrita"
            continue

        if rota not in esperado:
            print(f"{rota:<14} DIFERENTE  {classe} — rota só no novo")
            falhou = falhou or classe == "estrita"
            continue

        if modo_v3:
            estado = (cobertura or {}).get("estado")
            alvo = recorte(rota, esperado[rota], cobertura or {})
            # Rota completa tem de ser simétrica: campo que a v3 deixar de devolver
            # precisa aparecer como diferença, não como silêncio.
            msgs = (diff_parcial(alvo, novo[rota], rota) if estado == "parcial"
                    else diff(esperado[rota], novo[rota], rota))
            comparados = contar_campos(novo[rota])
            campos = contar_campos(alvo)
            parcial = estado == "parcial"
            rotulo = (cobertura or {}).get("rotulo") or (
                f"parcial {comparados}/{campos} campos" if parcial else "completa")
        else:
            msgs = diff(esperado[rota], novo[rota], rota)
            rotulo = "completa"

        if not msgs:
            print(f"{rota:<14} PASS       {classe} ({rotulo})")
            continue

        desfecho = "RECALL" if classe == "resultado" else "DIFERENTE"
        print(f"{rota:<14} {desfecho:<10} {classe} ({rotulo}) — {len(msgs)} diferença(s)")
        for m in msgs[:8]:
            print(f"                {m}")
        if len(msgs) > 8:
            print(f"                … +{len(msgs) - 8}")
        if classe == "estrita":
            falhou = True
    print(f"\nfonte: {origem}")
    return falhou


def main(argv=None):
    parser = argparse.ArgumentParser(description="Compara o contrato de leitura do v2 com o da v3.")
    parser.add_argument("esperado", nargs="?", default="docs/paridade/esperado.json",
                        help="fixture de referência capturado do v2")
    parser.add_argument("novo", nargs="?", help="fixture novo (modo arquivo)")
    parser.add_argument("--v3", metavar="URL", help="compara ao vivo contra a v3 nesta URL")
    parser.add_argument("--exigir-completo", action="store_true",
                        help="rota pendente também reprova (quando a paridade for completa)")
    args = parser.parse_args(argv)

    esperado = carrega(args.esperado)

    if args.v3:
        base = args.v3
        try:
            novo = capturar_v3(base)
        except Exception as e:  # noqa: BLE001 — o relatório precisa dizer o que faltou
            print(f"não foi possível capturar a v3 em {base}: {e}", file=sys.stderr)
            return 2
        falhou = comparar(esperado, novo, args.exigir_completo, base, modo_v3=True)
    else:
        if not args.novo:
            parser.error("informe o fixture novo ou use --v3 URL")
        novo = carrega(args.novo)
        falhou = comparar(esperado, novo, args.exigir_completo, args.novo, modo_v3=False)

    print("\nRESULTADO:", "falhou" if falhou else "ok")
    return 1 if falhou else 0


if __name__ == "__main__":
    sys.exit(main())
