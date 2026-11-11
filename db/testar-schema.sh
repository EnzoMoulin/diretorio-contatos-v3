#!/usr/bin/env bash
# =============================================================================
#  db/testar-schema.sh — aplica o DDL num banco descartável e roda as invariantes
#
#  Conexão: variáveis padrão do psql (PGHOST, PGPORT, PGUSER, PGPASSWORD) ou
#  DATABASE_URL, do jeito que o Neon entrega.
#
#    ./db/testar-schema.sh
#    PGHOST=/tmp/pgsock PGPORT=55432 ./db/testar-schema.sh
#    DATABASE_URL='postgresql://user:senha@host/neondb?sslmode=require' ./db/testar-schema.sh
#
#  SEGURANÇA: o script só trabalha em banco terminado em `_teste`. O nome vem de
#  DB_BASE (padrão `diretorio`) e o sufixo é sempre acrescentado, então o banco
#  de produção nunca é o alvo. Ao fim, o banco de teste é apagado.
# =============================================================================
set -euo pipefail

AQUI="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE="${DB_BASE:-diretorio}"
NOME="${BASE%_teste}_teste"
LOG="$(mktemp)"
trap 'rm -f "$LOG"' EXIT

# -- monta a função de conexão (URL do Neon ou variáveis do psql) -------------
URL_PREFIXO=""
URL_QUERY=""
if [[ -n "${DATABASE_URL:-}" ]]; then
  SEM_QUERY="${DATABASE_URL%%\?*}"
  [[ "$DATABASE_URL" == *"?"* ]] && URL_QUERY="?${DATABASE_URL#*\?}"
  case "$SEM_QUERY" in
    */*) URL_PREFIXO="${SEM_QUERY%/*}" ;;
    *)   echo "DATABASE_URL sem nome de banco, não sei montar o alvo" >&2; exit 1 ;;
  esac
fi

conectar() {  # conectar <banco> [args do psql...]
  local banco="$1"; shift
  if [[ -n "$URL_PREFIXO" ]]; then
    psql "${URL_PREFIXO}/${banco}${URL_QUERY}" "$@"
  else
    psql -d "$banco" "$@"
  fi
}

limpar_saida() {  # tira ruído do psql, mantém PASS/FALHOU/OK
  sed -e 's/^psql:[^ ]*: NOTICE:  //' \
      -e '/^DO$/d' -e '/^BEGIN$/d' -e '/^COMMIT$/d' \
      -e '/^CREATE /d' -e '/^DROP /d' -e '/^INSERT /d' -e '/^UPDATE /d' \
      -e '/^TRUNCATE /d' -e '/^COMMENT$/d' -e '/^SET$/d' -e '/^ALTER TABLE$/d'
}

echo "== banco de teste: $NOME"
conectar postgres -q -c "DROP DATABASE IF EXISTS \"$NOME\";" -c "CREATE DATABASE \"$NOME\";"

echo "== aplicando db/schema.sql"
conectar "$NOME" -q -v ON_ERROR_STOP=1 -f "$AQUI/schema.sql" >"$LOG" 2>&1 || {
  echo "FALHOU: schema.sql não aplicou" >&2; cat "$LOG" >&2; exit 1;
}

echo "== aplicando db/schema.sql pela 2ª vez (idempotência)"
if ! conectar "$NOME" -q -v ON_ERROR_STOP=1 -f "$AQUI/schema.sql" >"$LOG" 2>&1; then
  echo "FALHOU: o DDL não é idempotente" >&2; cat "$LOG" >&2; exit 1
fi
echo "PASS  schema.sql roda duas vezes sem erro"

echo "== invariantes"
if ! conectar "$NOME" -v ON_ERROR_STOP=1 -f "$AQUI/verificar-schema.sql" >"$LOG" 2>&1; then
  limpar_saida <"$LOG" >&2
  echo "FALHOU: invariante quebrada (saída acima)" >&2
  conectar postgres -q -c "DROP DATABASE IF EXISTS \"$NOME\";" >/dev/null 2>&1 || true
  exit 1
fi
limpar_saida <"$LOG"

# -- carga do seed (opcional: basta apontar o JSON) --------------------------
if [[ -n "${SEED_JSON:-}" ]]; then
  echo "== carga do seed: $SEED_JSON"
  SEED_SQL="$(mktemp)"
  trap 'rm -f "$LOG" "$SEED_SQL"' EXIT
  python3 "$AQUI/carregar-seed.py" "$SEED_JSON" >"$SEED_SQL"
  if ! conectar "$NOME" -q -v ON_ERROR_STOP=1 -f "$SEED_SQL" >"$LOG" 2>&1; then
    echo "FALHOU: o seed não entra no schema" >&2; cat "$LOG" >&2; exit 1
  fi
  echo "== invariantes da carga"
  if ! conectar "$NOME" -v ON_ERROR_STOP=1 -f "$AQUI/verificar-carga.sql" >"$LOG" 2>&1; then
    limpar_saida <"$LOG" >&2
    echo "FALHOU: invariante de carga quebrada" >&2
    conectar postgres -q -c "DROP DATABASE IF EXISTS \"$NOME\";" >/dev/null 2>&1 || true
    exit 1
  fi
  limpar_saida <"$LOG"

  echo "== invariantes de plano (o índice é usado pelo motor)"
  if ! conectar "$NOME" -v ON_ERROR_STOP=1 -f "$AQUI/verificar-plano.sql" >"$LOG" 2>&1; then
    limpar_saida <"$LOG" >&2
    echo "FALHOU: invariante de plano quebrada" >&2
    conectar postgres -q -c "DROP DATABASE IF EXISTS \"$NOME\";" >/dev/null 2>&1 || true
    exit 1
  fi
  limpar_saida <"$LOG" | grep -v '^INFO' 
fi

echo "== limpando: DROP DATABASE $NOME"
conectar postgres -q -c "DROP DATABASE \"$NOME\";" >/dev/null
echo "OK"
