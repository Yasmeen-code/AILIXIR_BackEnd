#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear --no-interaction 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction
else
  echo "Skipping migrations because RUN_MIGRATIONS=${RUN_MIGRATIONS}"
fi

exec "$@"
