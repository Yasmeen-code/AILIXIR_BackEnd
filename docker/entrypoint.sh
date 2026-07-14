#!/bin/sh
set -e

cd /var/www/html

# Generate .env from .env.hf on HF Spaces
if [ "${RUN_MODE}" = "hf" ] && [ -f /var/www/html/.env.hf ]; then
    cp /var/www/html/.env.hf /var/www/html/.env
    echo "QUEUE_CONNECTION=sync" >> /var/www/html/.env
    echo "APP_DEBUG=true" >> /var/www/html/.env
    [ -n "${APP_KEY}" ] && echo "APP_KEY=${APP_KEY}" >> /var/www/html/.env
    [ -n "${GOOGLE_CLIENT_ID}" ] && echo "GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}" >> /var/www/html/.env
    [ -n "${GOOGLE_CLIENT_SECRET}" ] && echo "GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}" >> /var/www/html/.env
    [ -n "${APP_URL}" ] && echo "GOOGLE_REDIRECT_URI=${APP_URL}/api/user/auth/google" >> /var/www/html/.env
    echo ".env generated from .env.hf"
fi

php artisan config:clear --no-interaction 2>/dev/null || true

# Create SQLite database file if it doesn't exist
if [ "${DB_CONNECTION}" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-database/ailixir.sqlite}"
    DB_DIR=$(dirname "$DB_PATH")
    mkdir -p "$DB_DIR"
    touch "$DB_PATH"
    chmod 664 "$DB_PATH"
    echo "SQLite database prepared at $DB_PATH"
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
else
    echo "Skipping migrations because RUN_MIGRATIONS=${RUN_MIGRATIONS}"
fi

# Seed verified test users for CI testing (idempotent via firstOrCreate)
php artisan db:seed --class=TestUserSeeder --force --no-interaction 2>/dev/null || true

# Auto-generate APP_KEY on first boot if not set via env or .env
if [ -z "${APP_KEY}" ] && ! grep -q '^APP_KEY=' /var/www/html/.env 2>/dev/null; then
    NEW_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));' 2>/dev/null)"
    if [ -n "${NEW_KEY}" ]; then
        echo "APP_KEY=${NEW_KEY}" >> /var/www/html/.env
        echo "APP_KEY auto-generated and written to .env"
    else
        echo "WARNING: Could not generate APP_KEY"
    fi
fi

# Source RUN_MODE from .env if not already set (HF Spaces sync writes it there)
if [ -z "${RUN_MODE}" ] && [ -f /var/www/html/.env ]; then
    RUN_MODE=$(grep -oP '^RUN_MODE=\K.*' /var/www/html/.env 2>/dev/null | head -1 || true)
fi

# If RUN_MODE=hf, run with supervisord (Hugging Face Spaces)
if [ "${RUN_MODE}" = "hf" ]; then
    echo "Starting AILIXIR on Hugging Face Spaces..."
    exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi

exec "$@"
