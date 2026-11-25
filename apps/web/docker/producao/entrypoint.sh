#!/bin/sh
# Entrypoint do v3 no Render free.
#
# O free tier não tem pre-deploy command (é recurso de plano pago), então
# toda preparação acontece aqui, antes de abrir a porta: configuração em
# cache, migrations e symlink do storage. Serviço dormindo não executa nada;
# no wake, migrate --force roda de novo e é no-op quando não há migration nova.

set -eu

: "${PORT:=10000}"
export PORT

: "${APP_KEY:?APP_KEY ausente: defina no painel do Render (generateValue)}"

# Neon entrega a string como DATABASE_URL; o Laravel lê DB_URL (config/database.php).
if [ -z "${DB_URL:-}" ] && [ -n "${DATABASE_URL:-}" ]; then
  export DB_URL="$DATABASE_URL"
fi

: "${DB_URL:?DB_URL (ou DATABASE_URL) ausente: defina a conexão do Neon no painel}"

# Só ${PORT} é substituído — $uri, $query_string e afins do nginx ficam intactos.
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Falha cedo e alto se APP_KEY ou DB_URL estiverem faltando.
php artisan config:cache

php artisan migrate --force

[ -L public/storage ] || php artisan storage:link || true

php-fpm -D
exec nginx -g 'daemon off;'
