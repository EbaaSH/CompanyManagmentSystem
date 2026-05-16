#!/bin/sh
set -e

echo "=== Laravel Docker Entrypoint ==="
echo "PHP version: $(php -v | head -1)"

CERT_PATH="/var/www/html/storage/certs/aiven-ca.pem"

# ─────────────────────────────────────
# 1. Permissions
# ─────────────────────────────────────
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# ─────────────────────────────────────
# 2. DB connection
# ─────────────────────────────────────
echo "Testing DB connection..."
MAX_TRIES=10
TRIES=0
until php -r "
    try {
        new PDO(
            'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
            '${DB_USERNAME}', '${DB_PASSWORD}',
            [PDO::MYSQL_ATTR_SSL_CA => '${CERT_PATH}', PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
        );
        echo 'OK';
    } catch(Exception \$e) { fwrite(STDERR, \$e->getMessage().PHP_EOL); exit(1); }
" 2>/tmp/pdo_err.txt | grep -q "OK"; do
    TRIES=$((TRIES+1))
    [ "$TRIES" -ge "$MAX_TRIES" ] && echo "❌ DB failed: $(cat /tmp/pdo_err.txt)" && exit 1
    echo "  DB attempt $TRIES: $(cat /tmp/pdo_err.txt 2>/dev/null)"
    sleep 3
done
echo "✅ DB connected"

# ─────────────────────────────────────
# 3. Migrations
# ─────────────────────────────────────
php artisan migrate --force || { echo "❌ Migrations failed"; exit 1; }
echo "✅ Migrations done"

# ─────────────────────────────────────
# 4. Laravel boot check — THIS SHOWS THE 500 CAUSE
# ─────────────────────────────────────
echo ""
echo "=== LARAVEL BOOT DIAGNOSTICS ==="

echo "-- php artisan about --"
php artisan about 2>&1 || true

echo ""
echo "-- Clearing bootstrap/cache --"
rm -f /var/www/html/bootstrap/cache/config.php
rm -f /var/www/html/bootstrap/cache/routes-v7.php
rm -f /var/www/html/bootstrap/cache/services.php
rm -f /var/www/html/bootstrap/cache/packages.php

echo ""
echo "-- php artisan config:cache (errors shown here) --"
php artisan config:cache 2>&1 || true

echo ""
echo "-- php artisan route:cache (errors shown here) --"
php artisan route:cache 2>&1 || true

echo ""
echo "-- php artisan view:cache (errors shown here) --"
php artisan view:cache 2>&1 || true

echo ""
echo "-- Checking storage/logs/laravel.log --"
LOG_FILE="/var/www/html/storage/logs/laravel.log"
if [ -f "$LOG_FILE" ] && [ -s "$LOG_FILE" ]; then
    echo "Laravel log found — last 60 lines:"
    tail -60 "$LOG_FILE"
else
    echo "(log file empty or not yet created)"
fi

echo ""
echo "-- Simulating a request to / --"
php -r "
    \$_SERVER['HTTP_HOST']   = 'localhost';
    \$_SERVER['REQUEST_URI'] = '/';
    \$_SERVER['REQUEST_METHOD'] = 'GET';
    define('LARAVEL_START', microtime(true));
    try {
        require '/var/www/html/public/index.php';
    } catch (\Throwable \$e) {
        echo 'BOOT ERROR: ' . \$e->getMessage() . PHP_EOL;
        echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . PHP_EOL;
        echo \$e->getTraceAsString();
    }
" 2>&1 | head -80 || true

echo ""
echo "=== END DIAGNOSTICS ==="

# ─────────────────────────────────────
# 5. Storage link
# ─────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ─────────────────────────────────────
# 6. Apache
# ─────────────────────────────────────
echo "ServerName localhost" >> /etc/apache2/apache2.conf
echo "=== Starting Apache ==="
exec apache2-foreground