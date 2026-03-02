#!/bin/bash
set -e

# Ensure SQLite database file exists before migrations
touch /app/database/database.sqlite

# Run Laravel setup
if [ "${RAILPACK_SKIP_MIGRATIONS}" != "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
    echo "Seeding database..."
    php artisan db:seed --force
fi

php artisan storage:link 2>/dev/null || true
php artisan optimize:clear
php artisan optimize

echo "Starting Laravel server..."
docker-php-entrypoint --config /Caddyfile --adapter caddyfile 2>&1
