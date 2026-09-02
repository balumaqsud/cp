#!/bin/sh
set -e

export SERVER_NAME=":${PORT:-10000}"

if [ -f /etc/frankenphp/Caddyfile ]; then
    CADDYFILE=/etc/frankenphp/Caddyfile
else
    CADDYFILE=/etc/caddy/Caddyfile
fi

exec frankenphp run --config "$CADDYFILE"
