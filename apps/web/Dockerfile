# syntax=docker/dockerfile:1
#
# Diretório de Contatos v3 — Laravel 13 (PHP 8.4) + nginx + php-fpm
#
# Por que Docker: o Render não oferece runtime PHP nativo (só JS/TS, Python,
# Ruby, Go, Rust e Elixir). No free tier não existe pre-deploy command, então
# as migrations rodam no entrypoint, antes do php-fpm subir.
#
# Build (contexto apps/web):
#   docker build -t diretorio-contatos-v3 apps/web
# Local:
#   docker run --rm -p 10000:10000 --env-file apps/web/.env diretorio-contatos-v3

# ---------------------------------------------------------------- dependências
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist \
      --no-progress --optimize-autoloader --ignore-platform-reqs

# --------------------------------------------------------------------- runtime
FROM php:8.4-fpm-alpine AS runtime

# gettext  -> envsubst (porta do Render no nginx)
# icu-dev  -> intl
# libzip-dev -> zip
# postgresql-dev -> pdo_pgsql (Neon)
RUN apk add --no-cache nginx gettext icu-dev libzip-dev postgresql-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql zip opcache pcntl \
    && rm -rf /var/cache/apk/*

COPY docker/producao/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/producao/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/producao/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions \
                storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    PORT=10000

EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
