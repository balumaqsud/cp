FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions pdo_pgsql intl opcache zip

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0
# Placeholder so cache/assets can compile at build time.
# Real APP_SECRET comes from Render at runtime.
ENV APP_SECRET=build-time-placeholder

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && mkdir -p var \
    && php bin/console cache:clear --env=prod --no-debug \
    && php bin/console asset-map:compile --env=prod

# Render proxy in front: HTTP only, default Render port
ENV SERVER_NAME=:10000
EXPOSE 10000

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
