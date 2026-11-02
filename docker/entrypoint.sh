#!/bin/bash
set -e

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

# Hand off to the image's own entrypoint (starts nginx + FPM)
exec docker-php-serversideup-entrypoint "$@"