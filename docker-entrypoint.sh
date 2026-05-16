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
echo "Running migrations..."
php artisan migrate --force || { echo "❌ Migrations failed"; exit 1; }
echo "✅ Migrations done"

# ─────────────────────────────────────
# 4. Seeders — runs ONCE using a flag table
#    If you want to re-seed, delete the row:
#    DELETE FROM seed_flags WHERE name='seeded_v1';
# ─────────────────────────────────────
echo "Checking seed status..."

ALREADY_SEEDED=$(php -r "
    try {
        \$pdo = new PDO(
            'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
            '${DB_USERNAME}', '${DB_PASSWORD}',
            [PDO::MYSQL_ATTR_SSL_CA => '${CERT_PATH}', PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
        );
        \$pdo->exec(\"CREATE TABLE IF NOT EXISTS seed_flags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )\");
        \$row = \$pdo->query(\"SELECT id FROM seed_flags WHERE name='seeded_v1'\")->fetch();
        echo \$row ? 'YES' : 'NO';
    } catch(Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
    }
" 2>&1)

echo "  Seed flag check: $ALREADY_SEEDED"

if [ "$ALREADY_SEEDED" = "NO" ]; then
    echo "Running seeders for the first time..."
    if php artisan db:seed --force; then
        echo "✅ Seeders ran successfully"
        # Mark as seeded so it won't run again on next deploy
        php -r "
            \$pdo = new PDO(
                'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
                '${DB_USERNAME}', '${DB_PASSWORD}',
                [PDO::MYSQL_ATTR_SSL_CA => '${CERT_PATH}', PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
            );
            \$pdo->exec(\"INSERT IGNORE INTO seed_flags (name) VALUES ('seeded_v1')\");
        " 2>/dev/null || true
        echo "✅ Seed flag saved — seeds will NOT run again on next deploy"
    else
        echo "❌ Seeders FAILED — check output above"
        echo "   App will still start, but data may be missing"
    fi
elif [ "$ALREADY_SEEDED" = "YES" ]; then
    echo "✅ Already seeded — skipping (delete seed_flags row to re-seed)"
else
    echo "⚠️  Could not check seed flag: $ALREADY_SEEDED"
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