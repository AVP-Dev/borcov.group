#!/bin/bash
set -e

echo "=== Keyword Automation Platform: Starting ==="

# -------------------------------------------------------
# 1. Generate cookieValidationKey EXPLICITLY (AGENTS.md rule #1)
#    Never rely on php init script — it may silently leave it empty
# -------------------------------------------------------
COOKIE_KEY=$(openssl rand -base64 32)
echo "Generated cookieValidationKey"

# -------------------------------------------------------
# 2. Run `php init` in production mode
# -------------------------------------------------------
php /var/www/html/init --env=Production --overwrite=All

# -------------------------------------------------------
# 3. Inject cookieValidationKey into backend config
#    (after init, which may have set it to empty string)
# -------------------------------------------------------
BACKEND_CONFIG="/var/www/html/backend/config/main-local.php"
if [ -f "$BACKEND_CONFIG" ]; then
    # Replace empty cookieValidationKey with generated one
    sed -i "s/'cookieValidationKey' => ''/'cookieValidationKey' => '${COOKIE_KEY}'/" "$BACKEND_CONFIG"
    echo "Injected cookieValidationKey into backend config"
fi

# -------------------------------------------------------
# 4. Write DB config (PostgreSQL)
# -------------------------------------------------------
DB_HOST="${DB_HOST:-postgres}"
DB_NAME="${DB_NAME:-keyword_platform}"
DB_USER="${DB_USER:-yii2}"
DB_PASS="${DB_PASS:-secret}"

COMMON_CONFIG="/var/www/html/common/config/main-local.php"
cat > "$COMMON_CONFIG" << PHPEOF
<?php

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'pgsql:host=${DB_HOST};dbname=${DB_NAME}',
            'username' => '${DB_USER}',
            'password' => '${DB_PASS}',
            'charset' => 'utf8',
        ],
    ],
];
PHPEOF
echo "Written DB config (pgsql:host=${DB_HOST};dbname=${DB_NAME})"

# -------------------------------------------------------
# 5. Set ADMIN_PASSWORD from env or generate random
# -------------------------------------------------------
ADMIN_PASSWORD="${ADMIN_PASSWORD:-$(openssl rand -base64 16)}"
echo "Admin password is set (from env or randomly generated)"

# -------------------------------------------------------
# 6. Wait for PostgreSQL to be ready
# -------------------------------------------------------
echo "Waiting for PostgreSQL at ${DB_HOST}:5432..."
MAX_TRIES=30
COUNT=0
until php -r "
\$pdo = new PDO('pgsql:host=${DB_HOST};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASS}');
echo 'Connected';
" 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "ERROR: PostgreSQL not available after ${MAX_TRIES} attempts. Exiting."
        exit 1
    fi
    echo "  Attempt ${COUNT}/${MAX_TRIES}: PostgreSQL not ready, waiting 2s..."
    sleep 2
done
echo "PostgreSQL is ready!"

# -------------------------------------------------------
# 7. Run migrations (AGENTS.md rule #2: NO || echo — fail visibly!)
# -------------------------------------------------------
echo "Running migrations..."
php /var/www/html/yii migrate --interactive=0
echo "Migrations completed successfully"

# -------------------------------------------------------
# 8. Fix permissions
# -------------------------------------------------------
chown -R www-data:www-data \
    /var/www/html/backend/runtime \
    /var/www/html/backend/web/assets \
    /var/www/html/frontend/runtime \
    /var/www/html/frontend/web/assets \
    /var/www/html/console/runtime 2>/dev/null || true

echo "=== Starting supervisor (nginx + php-fpm) ==="
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
