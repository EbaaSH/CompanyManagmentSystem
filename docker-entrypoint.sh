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
# 2. DB connection (suppress deprecation warnings for PHP 8.5)
# ─────────────────────────────────────
echo "Testing DB connection..."
MAX_TRIES=10
TRIES=0
until php -d error_reporting=E_ERROR -r "
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
echo "Running migrations..."
php artisan migrate --force || { echo "❌ Migrations failed"; exit 1; }
echo "✅ Migrations done"

# ─────────────────────────────────────
# 4. Seeders — runs ONCE using a flag table
#    To re-seed: DELETE FROM seed_flags WHERE name='seeded_v1';
# ─────────────────────────────────────
echo "Checking seed status..."

# Write the check script to a file to avoid shell/PHP quoting issues
cat > /tmp/check_seed.php << 'PHPEOF'
<?php
error_reporting(E_ERROR); // suppress deprecation warnings
$cert = getenv('MYSQL_ATTR_SSL_CA');
try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        [
            PDO::MYSQL_ATTR_SSL_CA => $cert,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS seed_flags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $row = $pdo->query("SELECT id FROM seed_flags WHERE name='seeded_v1'")->fetch();
    echo $row ? 'YES' : 'NO';
} catch (Exception $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
PHPEOF

# Write the flag-saving script to a file too
cat > /tmp/save_seed_flag.php << 'PHPEOF'
<?php
error_reporting(E_ERROR);
$cert = getenv('MYSQL_ATTR_SSL_CA');
try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        [
            PDO::MYSQL_ATTR_SSL_CA => $cert,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
    $pdo->exec("INSERT IGNORE INTO seed_flags (name) VALUES ('seeded_v1')");
    echo 'FLAG_SAVED';
} catch (Exception $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
PHPEOF

SEED_STATUS=$(php /tmp/check_seed.php 2>/tmp/seed_err.txt)
echo "  Seed flag: $SEED_STATUS"

if [ "$SEED_STATUS" = "NO" ]; then
    echo "Running seeders for the first time..."
    if php artisan db:seed --force; then
        echo "✅ Seeders ran successfully"
        php /tmp/save_seed_flag.php 2>/dev/null && echo "✅ Seed flag saved — won't run again on next deploy" || echo "⚠️  Could not save seed flag"
    else
        echo "❌ Seeders FAILED — check output above"
    fi
elif [ "$SEED_STATUS" = "YES" ]; then
    echo "✅ Already seeded — skipping"
    echo "   (To re-seed: run DELETE FROM seed_flags WHERE name='seeded_v1' in Aiven Query Editor)"
else
    echo "❌ Seed check error: $(cat /tmp/seed_err.txt 2>/dev/null)"
    echo "   Skipping seeder to be safe"
fi

# ─────────────────────────────────────
# 5. Cache
# ─────────────────────────────────────
echo "Rebuilding caches..."
php artisan config:clear  || true
php artisan route:clear   || true
php artisan view:clear    || true
php artisan config:cache  || true
php artisan route:cache   || true
php artisan view:cache    || true
php artisan storage:link --force 2>/dev/null || true
echo "✅ All caches rebuilt"

# ─────────────────────────────────────
# 6. Apache
# ─────────────────────────────────────
echo "ServerName localhost" >> /etc/apache2/apache2.conf
echo "=== Starting Apache ==="
exec apache2-foreground