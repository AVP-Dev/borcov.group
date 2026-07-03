#!/bin/bash
set -e

echo "=============================================="
echo " Marketing Keyword Automation Platform"
echo " Container startup sequence"
echo "=============================================="

# -------------------------------------------------------
# 1. Generate cookieValidationKey EXPLICITLY (AGENTS.md rule #1)
# -------------------------------------------------------
COOKIE_KEY=$(openssl rand -base64 32)
echo "[1/5] Generated cookieValidationKey"

# -------------------------------------------------------
# 2. Generate local configs if missing (first run in fresh environment)
# -------------------------------------------------------
BACKEND_CONFIG="/var/www/html/backend/config/main-local.php"
FRONTEND_CONFIG="/var/www/html/frontend/config/main-local.php"
COMMON_CONFIG="/var/www/html/common/config/main-local.php"

if [ ! -f "$BACKEND_CONFIG" ] || [ ! -f "$FRONTEND_CONFIG" ]; then
    echo "[2/5] Generating local configs via php init..."
    yes | php /var/www/html/init --env=Production --overwrite=All 2>/dev/null || true
    echo "      php init completed"
else
    echo "[2/5] Local configs already exist, skipping php init"
fi

# -------------------------------------------------------
# 3. Inject cookieValidationKey into backend + frontend configs
# -------------------------------------------------------
for cfg in "$BACKEND_CONFIG" "$FRONTEND_CONFIG"; do
    if [ -f "$cfg" ]; then
        php -r "
\$config = file_get_contents('${cfg}');
\$config = preg_replace(
    \"/'cookieValidationKey'\s*=>\s*'[^']*'/\",
    \"'cookieValidationKey' => '${COOKIE_KEY}'\",
    \$config
);
file_put_contents('${cfg}', \$config);
"
    fi
done
echo "[3/5] cookieValidationKey injected"

# -------------------------------------------------------
# 4. Write DB config (PostgreSQL) from environment variables
# -------------------------------------------------------
DB_HOST="${DB_HOST:-postgres}"
DB_NAME="${DB_NAME:-keyword_platform}"
DB_USER="${DB_USER:-yii2}"
DB_PASS="${DB_PASS:-secret}"

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
"
echo "[4/5] DB config written (pgsql:host=${DB_HOST};dbname=${DB_NAME})"

# -------------------------------------------------------
# 5. Set ADMIN_PASSWORD from env or generate random fallback
# -------------------------------------------------------
ADMIN_PASSWORD="${ADMIN_PASSWORD:-$(openssl rand -base64 16)}"
export ADMIN_PASSWORD
echo "[5/5] Admin password configured"

# -------------------------------------------------------
# Wait for PostgreSQL to accept TCP connections (max 60s)
# -------------------------------------------------------
echo "Waiting for PostgreSQL at ${DB_HOST}:5432..."
MAX_TRIES=30
COUNT=0
until php -r "
try {
    \$sock = @fsockopen('${DB_HOST}', 5432, \$errno, \$errstr, 2);
    if (\$sock) { fclose(\$sock); echo 'OK'; }
    else { exit(1); }
} catch (Exception \$e) { exit(1); }
" 2>/dev/null | grep -q "OK"; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "FATAL: PostgreSQL not available after ${MAX_TRIES} attempts. Exiting."
        exit 1
    fi
    echo "      Attempt ${COUNT}/${MAX_TRIES}: not ready, waiting 2s..."
    sleep 2
done
echo "      PostgreSQL is accepting connections!"

# -------------------------------------------------------
# Ensure the application user and database exist.
# This handles persistent volumes that were initialized
# with different credentials (e.g. by Coolify's service wizard).
# -------------------------------------------------------
echo "Ensuring database user '${DB_USER}' and database '${DB_NAME}' exist..."
php -r "
\$created = false;

// Try to connect as 'postgres' with the configured password
\$attempts = [
    ['postgres', '${DB_PASS}'],
    ['postgres', 'postgres'],
    ['postgres', ''],
    ['postgres', null],
];

foreach (\$attempts as [\$user, \$pass]) {
    try {
        \$dsn = 'pgsql:host=${DB_HOST};dbname=postgres';
        \$pdo = new PDO(\$dsn, \$user, \$pass ?? '');
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$created = true;
        break;
    } catch (Exception \$e) {
        continue;
    }
}

if (!\$created) {
    echo \"  WARNING: could not connect as superuser to create '${DB_USER}'\n\";
    echo \"  (the application will attempt direct connection below)\n\";
    exit(0);
}

// Check if role exists
\$stmt = \$pdo->query(\"SELECT 1 FROM pg_roles WHERE rolname = '${DB_USER}'\");
if (\$stmt->fetch() === false) {
    \$pdo->exec(\"CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASS}'\");
    echo \"  created user ${DB_USER}\n\";
} else {
    echo \"  user '${DB_USER}' already exists\n\";
}

// Check if database exists
\$stmt = \$pdo->query(\"SELECT 1 FROM pg_database WHERE datname = '${DB_NAME}'\");
if (\$stmt->fetch() === false) {
    \$pdo->exec(\"CREATE DATABASE ${DB_NAME} OWNER ${DB_USER}\");
    echo \"  created database ${DB_NAME}\n\";
} else {
    echo \"  database '${DB_NAME}' already exists\n\";
}

// Grant privileges
\$pdo->exec(\"GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER}\");
echo \"  privileges granted\n\";
"
echo "      Database setup complete"

# -------------------------------------------------------
# Run migrations (fail visibly on error!)
# -------------------------------------------------------
echo "Running migrations..."
php /var/www/html/yii migrate --interactive=0
echo "      Migrations completed successfully"

# -------------------------------------------------------
# Set admin user password from ADMIN_PASSWORD env var
# -------------------------------------------------------
echo "Setting admin user password..."
php /var/www/html/yii admin/set-password
echo "      Admin password set"

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
