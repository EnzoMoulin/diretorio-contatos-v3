# syntax=docker/dockerfile:1
#
# Diretório de Contatos v3 — Laravel 13 (PHP 8.4) + nginx + php-fpm
#
# Por que Docker: o Render não oferece runtime PHP nativo (só JS/TS, Python,
# Ruby, Go, Rust e Elixir). No free tier não existe pre-deploy command, então
# as migrations rodam no entrypoint, antes do php-fpm subir.
#
# Build:  docker build -t diretorio-contatos-v3 .
# Local:  docker run --rm -p 10000:10000 --env-file .env diretorio-contatos-v3

# ---------------------------------------------------------------- dependências
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist \
      --no-progress --optimize-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --ignore-platform-reqs

# ---------------------------------------------------------------------- assets
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# --------------------------------------------------------------------- runtime
FROM php:8.4-fpm-alpine AS runtime

# gettext  -> envsubst (porta do Render no nginx)
# icu-dev  -> intl
# libzip-dev -> zip
# postgresql-dev -> pdo_pgsql (Neon)
RUN apk add --no-cache nginx gettext icu-dev libzip-dev postgresql-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql zip opcache pcntl \
    && rm -rf /var/cache/apk/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /app
COPY --from=assets /app /app

RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    PORT=10000

EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
