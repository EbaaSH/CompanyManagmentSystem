#!/bin/sh

echo "Waiting for database..."

sleep 15

echo "Running migrations..."

php artisan migrate --force || true

echo "Running seeders..."

php artisan db:seed --force || true

echo "Caching..."

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Starting Apache..."

apache2-foreground