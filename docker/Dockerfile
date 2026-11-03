# 1. PHP dependencies (cached on composer.json / composer.lock)
FROM serversideup/php:8.5.12-cli AS deps
USER root
RUN install-php-extensions intl
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# 2. Frontend assets (access to vendor for PHP package assets)
FROM node:26-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci --ignore-scripts --no-audit --no-fund
COPY --from=deps /app/vendor ./vendor
COPY resources/ ./resources/
COPY vite.config.js ./
RUN npm run build

# 3. Optimised classmap-authoritative autoloader
FROM deps AS vendor
COPY src/ ./src/
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# 4. Production Runtime
FROM serversideup/php:8.5.12-fpm-nginx AS runtime

ENV SSL_MODE=off \
    PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=128 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_OPCACHE_INTERNED_STRINGS_BUFFER=16 \
    PHP_FPM_PM_CONTROL=ondemand \
    PHP_FPM_PM_MAX_CHILDREN=10 \
    PHP_FPM_PROCESS_IDLE_TIMEOUT=30s

USER root
RUN install-php-extensions intl
WORKDIR /var/www/html

# Copy application files (read-only for www-data)
COPY bootstrap.php console ./
COPY config/ ./config/
COPY migrations/ ./migrations/
COPY public/ ./public/
COPY resources/views/ ./resources/views/
COPY src/ ./src/
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Prepare writeable directories for runtime cache/logs only
RUN install -d -m 0775 -o www-data -g www-data var var/cache var/log

COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint.sh
USER www-data

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/init"]

# 5. Test Stage (CI only, never deployed)
FROM runtime AS test
ENTRYPOINT []
CMD []
USER root
COPY composer.json composer.lock ./
COPY phpunit.xml pint.json rector.php ./
COPY tests/ ./tests/
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --prefer-dist --no-interaction --no-progress && \
    chown -R www-data:www-data vendor
RUN install -d -m 0775 -o www-data -g www-data .phpunit.cache
USER www-data