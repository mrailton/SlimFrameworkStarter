# 1. PHP dependencies only (cached on composer.json / composer.lock)
FROM serversideup/php:8.5-cli AS deps
USER root
RUN install-php-extensions intl
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# 2. Frontend assets (needs vendor for the PHP package's JS)
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY --from=deps /app/vendor ./vendor
COPY . .
RUN npm run build

# 3. Optimised autoloader (needs app source)
FROM deps AS vendor
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# 4. Runtime
FROM serversideup/php:8.5-fpm-nginx AS runtime

ENV SSL_MODE=off \
    PHP_OPCACHE_ENABLE=1 \
    PHP_FPM_PM_CONTROL=ondemand \
    PHP_FPM_PM_MAX_CHILDREN=10 \
    PHP_FPM_PROCESS_IDLE_TIMEOUT=30s

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

USER root
RUN install-php-extensions intl
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
RUN composer install --prefer-dist --no-interaction && chown -R www-data:www-data vendor
USER www-data