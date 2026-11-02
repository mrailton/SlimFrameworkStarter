#!/bin/bash
set -e

echo "Running migrations..."
php console migrations:migrate --no-interaction

exec docker-php-serversideup-entrypoint "$@"