FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions pdo_pgsql intl opcache zip mbstring xml

# Render blocks binaries with file capabilities (cap_net_bind_service).
# Port 10000 does not need that cap.
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
ENV APP_SECRET=build-time-placeholder
ENV DATABASE_URL="postgresql://app:app@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && mkdir -p var \
    && php bin/console importmap:install --env=prod \
    && php bin/console cache:clear --env=prod --no-debug \
    && php bin/console asset-map:compile --env=prod

ENV SERVER_NAME=:10000
EXPOSE 10000

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
