FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions pdo_pgsql intl opcache zip mbstring xml

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Render blocks binaries with file capabilities (cap_net_bind_service).
# HTTP on $PORT does not need that cap.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcap2-bin \
    && setcap -r /usr/local/bin/frankenphp \
    && apt-get purge -y libcap2-bin \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0
# Build-time placeholders only. Override on Render at runtime:
#   APP_ENV=prod
#   APP_DEBUG=0
#   APP_SECRET=<non-empty random secret>
#   DATABASE_URL=postgresql://USER:PASS@HOST:5432/DB?serverVersion=16&charset=utf8
#     (add &sslmode=require when using Render's external Postgres hostname)
#   DEFAULT_URI=https://<your-service>.onrender.com
ENV APP_SECRET=build-time-placeholder
ENV DATABASE_URL="postgresql://app:app@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

# Render terminates TLS in front of the container.
ENV CADDY_GLOBAL_OPTIONS="auto_https off"
ENV SERVER_ROOT=/app/public

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && mkdir -p var/cache var/log var/share /data/caddy /config/caddy \
    && php bin/console importmap:install --env=prod \
    && php bin/console cache:clear --env=prod --no-debug \
    && php bin/console asset-map:compile --env=prod

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
