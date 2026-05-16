#!/bin/sh
set -e

echo "=== Laravel Docker Entrypoint ==="
echo "Working directory: $(pwd)"
echo "PHP version: $(php -v | head -1)"

# ─────────────────────────────────────
# 1. Verify the SSL cert for Aiven exists
# ─────────────────────────────────────
CERT_PATH="/var/www/html/storage/certs/aiven-ca.pem"
if [ ! -f "$CERT_PATH" ]; then
    echo "⚠️  WARNING: Aiven SSL cert not found at $CERT_PATH"
    echo "    Database connections requiring SSL will fail!"
    echo "    Make sure you committed the cert file to your repo."
else
    echo "✅ Aiven SSL cert found."
fi

# ─────────────────────────────────────
# 2. Fix storage & cache permissions
# ─────────────────────────────────────
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# ─────────────────────────────────────
# 3. Wait for database to be reachable
# ─────────────────────────────────────
echo "Waiting for database to be ready..."
MAX_TRIES=30
TRIES=0
until php -r "
    \$opts = [
        PDO::MYSQL_ATTR_SSL_CA => getenv('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        \$opts
    );
    echo 'DB OK';
" 2>/dev/null | grep -q "DB OK"; do
    TRIES=$((TRIES + 1))
    if [ "$TRIES" -ge "$MAX_TRIES" ]; then
        echo "❌ Database not reachable after $MAX_TRIES attempts. Check your DB_HOST, DB_PORT, credentials, and SSL cert."
        echo "    DB_HOST=$DB_HOST"
        echo "    DB_PORT=$DB_PORT"
        echo "    DB_DATABASE=$DB_DATABASE"
        echo "    DB_USERNAME=$DB_USERNAME"
        echo "    MYSQL_ATTR_SSL_CA=$MYSQL_ATTR_SSL_CA"
        exit 1
    fi
    echo "  Attempt $TRIES/$MAX_TRIES - waiting 3s..."
    sleep 3
done
echo "✅ Database is reachable."

# ─────────────────────────────────────
# 4. Run migrations (fail loudly if broken)
# ─────────────────────────────────────
echo "Running migrations..."
if ! php artisan migrate --force; then
    echo "❌ Migrations FAILED. Check output above."
    exit 1
fi
echo "✅ Migrations done."

# ─────────────────────────────────────
# 5. Seed only in non-production, or if you explicitly want it
# ─────────────────────────────────────
# Uncomment the line below ONLY if you want to seed on every deploy.
# In production this can cause duplicate data!
# php artisan db:seed --force || true

# ─────────────────────────────────────
# 6. Clear old caches, then rebuild
# ─────────────────────────────────────
echo "Clearing and rebuilding caches..."
php artisan config:clear || true
php artisan route:clear  || true
php artisan view:clear   || true
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true
echo "✅ Cache rebuilt."

# ─────────────────────────────────────
# 7. Create the storage symlink if needed
# ─────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

echo "=== Starting Apache ==="
exec apache2-foreground