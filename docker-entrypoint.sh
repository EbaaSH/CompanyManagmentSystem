#!/bin/sh
set -e

echo "=== Laravel Docker Entrypoint ==="
echo "Working directory: $(pwd)"
echo "PHP version: $(php -v | head -1)"

CERT_PATH="/var/www/html/storage/certs/aiven-ca.pem"

# ─────────────────────────────────────
# 1. Verify SSL cert
# ─────────────────────────────────────
if [ ! -f "$CERT_PATH" ]; then
    echo "❌ Aiven SSL cert NOT found at $CERT_PATH — STOPPING."
    exit 1
else
    echo "✅ Aiven SSL cert found."
    openssl x509 -noout -subject -issuer -enddate -in "$CERT_PATH" 2>/dev/null \
        && echo "   ^^^ Cert is valid/parseable" \
        || echo "⚠️  Cert file exists but openssl cannot parse it — it may be corrupted or wrong file!"
fi

# ─────────────────────────────────────
# 2. Permissions
# ─────────────────────────────────────
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# ─────────────────────────────────────
# 3. Raw TCP test
# ─────────────────────────────────────
echo ""
echo "--- Step 1: TCP connectivity ---"
if timeout 10 bash -c "echo > /dev/tcp/${DB_HOST}/${DB_PORT}" 2>/dev/null; then
    echo "✅ TCP: Can reach ${DB_HOST}:${DB_PORT}"
else
    echo "❌ TCP: CANNOT reach ${DB_HOST}:${DB_PORT}"
    echo "   Check: Is the Aiven service running? Correct host/port in env vars?"
    exit 1
fi

# ─────────────────────────────────────
# 4. TLS handshake test
# ─────────────────────────────────────
echo ""
echo "--- Step 2: TLS handshake ---"
TLS_RESULT=$(echo "Q" | timeout 10 openssl s_client \
    -connect "${DB_HOST}:${DB_PORT}" \
    -CAfile "${CERT_PATH}" \
    -starttls mysql 2>&1 || true)

echo "$TLS_RESULT" | grep -E "^(SSL|Verify|depth|subject|issuer|CONNECTED|error|SSL handshake)" | head -20 || true

if echo "$TLS_RESULT" | grep -q "Verify return code: 0"; then
    echo "✅ TLS: Cert verification passed"
elif echo "$TLS_RESULT" | grep -q "CONNECTED"; then
    echo "⚠️  TLS: Connected but cert verification issue — check cert output above"
else
    echo "❌ TLS: Could not complete handshake"
    echo "   Try re-downloading the CA cert from Aiven Console → Connection Information"
fi

# ─────────────────────────────────────
# 5. MySQL CLI test (clearest error messages)
# ─────────────────────────────────────
echo ""
echo "--- Step 3: MySQL CLI login ---"
if mysql \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    --password="${DB_PASSWORD}" \
    --ssl-ca="${CERT_PATH}" \
    --ssl-verify-server-cert=false \
    --connect-timeout=10 \
    -e "SELECT 'MySQL CLI OK';" 2>&1; then
    echo "✅ MySQL CLI: Connected successfully"
else
    echo "❌ MySQL CLI: Connection failed — error message above explains why"
    echo "   Common causes:"
    echo "   - Wrong DB_PASSWORD (check Render env vars, no quotes or spaces)"
    echo "   - Wrong DB_USERNAME"
    echo "   - Database '${DB_DATABASE}' does not exist on Aiven"
fi

# ─────────────────────────────────────
# 6. PDO / Laravel connection test
# ─────────────────────────────────────
echo ""
echo "--- Step 4: PDO (Laravel) connection ---"
MAX_TRIES=10
TRIES=0

until php -r "
    try {
        \$pdo = new PDO(
            'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
            '${DB_USERNAME}',
            '${DB_PASSWORD}',
            [
                PDO::MYSQL_ATTR_SSL_CA => '${CERT_PATH}',
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]
        );
        echo 'PDO_OK';
    } catch (Exception \$e) {
        fwrite(STDERR, \$e->getMessage() . PHP_EOL);
        exit(1);
    }
" 2>/tmp/pdo_err.txt | grep -q "PDO_OK"; do
    TRIES=$((TRIES + 1))
    echo "  Attempt $TRIES/$MAX_TRIES — $(cat /tmp/pdo_err.txt 2>/dev/null)"
    if [ "$TRIES" -ge "$MAX_TRIES" ]; then
        echo "❌ PDO connection failed after $MAX_TRIES attempts."
        exit 1
    fi
    sleep 3
done
echo "✅ PDO connected!"

# ─────────────────────────────────────
# 7. Migrations
# ─────────────────────────────────────
echo ""
echo "--- Step 5: Migrations ---"
php artisan migrate --force || { echo "❌ Migrations failed"; exit 1; }
echo "✅ Migrations done."

# ─────────────────────────────────────
# 8. Cache
# ─────────────────────────────────────
echo "Rebuilding caches..."
php artisan config:clear  || true
php artisan route:clear   || true
php artisan view:clear    || true
php artisan config:cache  || true
php artisan route:cache   || true
php artisan view:cache    || true
php artisan storage:link --force 2>/dev/null || true
echo "✅ All caches rebuilt."

echo ""
echo "=== Starting Apache ==="
exec apache2-foreground