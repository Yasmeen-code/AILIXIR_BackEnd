#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear --no-interaction 2>/dev/null || true
php artisan migrate --force --no-interaction

exec "$@"
