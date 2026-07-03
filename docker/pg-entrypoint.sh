#!/bin/bash
set -e

PGDATA="${PGDATA:-/var/lib/postgresql/data}"

if [ -d "$PGDATA" ] && [ -f "$PGDATA/PG_VERSION" ]; then
    echo "[pg-entrypoint] Existing database found. Ensuring required roles..."

    # Allow passwordless auth from Docker network so the app container
    # can create/verify the database user if bootstrap fails.
    if [ -f "$PGDATA/pg_hba.conf" ] && ! grep -q "host all all 172.0.0.0/8 trust" "$PGDATA/pg_hba.conf"; then
        sed -i '1ihost all all 172.0.0.0/8 trust' "$PGDATA/pg_hba.conf"
        echo "[pg-entrypoint] Added trust auth for 172.0.0.0/8"
    fi

    # Bootstrap required roles via single-user mode (bypasses auth).
    # This is the ONLY way to create roles when the superuser password
    # is unknown (e.g., Coolify-initialized volume with custom credentials).
    # set +e: CREATE ROLE/DATABASE fail if already exist (harmless)
    set +e
    su-exec postgres postgres --single -D "$PGDATA" template1 <<EOSQL 2>&1
CREATE ROLE postgres LOGIN SUPERUSER PASSWORD '${POSTGRES_PASSWORD}';
CREATE ROLE ${POSTGRES_USER:-yii2} LOGIN PASSWORD '${POSTGRES_PASSWORD}';
CREATE DATABASE ${POSTGRES_DB:-keyword_platform} OWNER ${POSTGRES_USER:-yii2};
EOSQL
    set -e

    echo "[pg-entrypoint] Single-user bootstrap completed."
fi

exec /usr/local/bin/docker-entrypoint.sh "$@"
