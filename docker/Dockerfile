# 1. PHP dependencies only (cached on composer.json / composer.lock)
FROM serversideup/php:8.5-cli AS deps
USER root
RUN install-php-extensions intl
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# 2. Frontend assets (needs vendor for the PHP package's JS)
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts --no-audit --no-fund
COPY --from=deps /app/vendor ./vendor
COPY resources/ ./resources/
COPY vite.config.js ./
RUN npm run build

# 3. Optimised autoloader (needs app source)
FROM deps AS vendor
COPY src/ ./src/
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# 4. Runtime
FROM serversideup/php:8.5-fpm-nginx AS runtime

ENV SSL_MODE=off \
    PHP_OPCACHE_ENABLE=1 \
    PHP_FPM_PM_CONTROL=ondemand \
    PHP_FPM_PM_MAX_CHILDREN=10 \
    PHP_FPM_PROCESS_IDLE_TIMEOUT=30s

USER root
RUN install-php-extensions intl
WORKDIR /var/www/html
COPY --chown=www-data:www-data bootstrap.php console ./
COPY --chown=www-data:www-data config/ ./config/
COPY --chown=www-data:www-data migrations/ ./migrations/
COPY --chown=www-data:www-data public/ ./public/
COPY --chown=www-data:www-data resources/views/ ./resources/views/
COPY --chown=www-data:www-data src/ ./src/
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN install -d -o www-data -g www-data var var/cache var/log
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint.sh
USER www-data

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/init"]

# 5. Test (CI only, never deployed)
FROM runtime AS test
ENTRYPOINT []
CMD []
USER root
COPY composer.json composer.lock ./
COPY phpunit.xml pint.json rector.php ./
COPY tests/ ./tests/
RUN composer install --prefer-dist --no-interaction --no-progress && chown -R www-data:www-data vendor
RUN install -d -o www-data -g www-data .phpunit.cache
USER www-data