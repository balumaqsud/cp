#!/bin/sh
set -e

if [ "${APP_ENV:-prod}" = "prod" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod
fi

export SERVER_NAME=":${PORT:-10000}"

if [ -f /etc/frankenphp/Caddyfile ]; then
    CADDYFILE=/etc/frankenphp/Caddyfile
else
    CADDYFILE=/etc/caddy/Caddyfile
fi

exec frankenphp run --config "$CADDYFILE"
