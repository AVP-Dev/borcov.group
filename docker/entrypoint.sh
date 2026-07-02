#!/bin/bash
set -e

echo "=============================================="
echo " Marketing Keyword Automation Platform"
echo " Container startup sequence"
echo "=============================================="

# -------------------------------------------------------
# 1. Generate cookieValidationKey EXPLICITLY (AGENTS.md rule #1)
#    NEVER rely on php init to set it — it may leave empty string
#    resulting in every HTTP request failing with 500 CSRF error
# -------------------------------------------------------
COOKIE_KEY=$(openssl rand -base64 32)
echo "[1/7] Generated cookieValidationKey"

# -------------------------------------------------------
# 2. Run `php init` in production non-interactive mode
# -------------------------------------------------------
echo "[2/7] Running php init (Production mode)..."
php /var/www/html/init --env=Production --overwrite=All
echo "      php init completed"

# -------------------------------------------------------
# 3. Inject cookieValidationKey into backend config
#    init may have set random key via setCookieValidationKey,
#    but we overwrite with our own to be 100% sure it's non-empty
# -------------------------------------------------------
BACKEND_CONFIG="/var/www/html/backend/config/main-local.php"
if [ -f "$BACKEND_CONFIG" ]; then
    # Use PHP to safely inject the key (avoids sed escaping issues)
    php -r "
\$config = file_get_contents('${BACKEND_CONFIG}');
\$config = preg_replace(
    \"/'cookieValidationKey'\s*=>\s*'[^']*'/\",
    \"'cookieValidationKey' => '${COOKIE_KEY}'\",
    \$config
);
file_put_contents('${BACKEND_CONFIG}', \$config);
echo 'cookieValidationKey injected into backend config\n';
"
else
    echo "ERROR: backend config not found at ${BACKEND_CONFIG}"
    exit 1
fi

# -------------------------------------------------------
# 4. Write DB config (PostgreSQL) from environment variables
# -------------------------------------------------------
DB_HOST="${DB_HOST:-postgres}"
DB_NAME="${DB_NAME:-keyword_platform}"
DB_USER="${DB_USER:-yii2}"
DB_PASS="${DB_PASS:-secret}"

COMMON_CONFIG="/var/www/html/common/config/main-local.php"
php -r "
file_put_contents('${COMMON_CONFIG}', '<?php

return [
    \'components\' => [
        \'db\' => [
            \'class\' => \yii\db\Connection::class,
            \'dsn\' => \'pgsql:host=${DB_HOST};dbname=${DB_NAME}\',
            \'username\' => \'${DB_USER}\',
            \'password\' => \'${DB_PASS}\',
            \'charset\' => \'utf8\',
        ],
    ],
];
');
echo 'DB config written (pgsql:host=${DB_HOST};dbname=${DB_NAME})\n';
"

echo "[4/7] DB config written"

# -------------------------------------------------------
# 5. Set ADMIN_PASSWORD from env or generate random fallback
# -------------------------------------------------------
ADMIN_PASSWORD="${ADMIN_PASSWORD:-$(openssl rand -base64 16)}"
export ADMIN_PASSWORD
echo "[5/7] Admin password configured"

# -------------------------------------------------------
# 6. Wait for PostgreSQL to be ready (max 60 seconds)
# -------------------------------------------------------
echo "[6/7] Waiting for PostgreSQL at ${DB_HOST}:5432..."
MAX_TRIES=30
COUNT=0
until php -r "
try {
    \$pdo = new PDO('pgsql:host=${DB_HOST};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASS}');
    echo 'OK';
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null | grep -q "OK"; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "FATAL: PostgreSQL not available after ${MAX_TRIES} attempts (${MAX_TRIES}x2s = $((MAX_TRIES * 2))s). Exiting."
        exit 1
    fi
    echo "      Attempt ${COUNT}/${MAX_TRIES}: not ready, waiting 2s..."
    sleep 2
done
echo "      PostgreSQL is ready!"

# -------------------------------------------------------
# 7. Run migrations (AGENTS.md rule #2: NO || echo — fail visibly!)
#    If migration fails → container fails → visible in Coolify logs
# -------------------------------------------------------
echo "[7/7] Running migrations..."
php /var/www/html/yii migrate --interactive=0
echo "      Migrations completed successfully"

# -------------------------------------------------------
# Final: Fix permissions and start services
# -------------------------------------------------------
echo "---"
echo "Fixing permissions..."
chown -R www-data:www-data \
    /var/www/html/backend/runtime \
    /var/www/html/backend/web/assets \
    /var/www/html/frontend/runtime \
    /var/www/html/frontend/web/assets \
    /var/www/html/console/runtime \
    2>/dev/null || true

echo "=============================================="
echo " All startup checks passed. Starting services."
echo "=============================================="
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
