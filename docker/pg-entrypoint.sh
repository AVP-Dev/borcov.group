#!/bin/bash
set -e

PGDATA="${PGDATA:-/var/lib/postgresql/data}"

if [ -d "$PGDATA" ] && [ -f "$PGDATA/PG_VERSION" ]; then
    # Patch pg_hba.conf to allow passwordless auth from Docker network
    # This handles the case where the persistent volume was initialized
    # by Coolify with unknown credentials and neither 'postgres' nor
    # 'yii2' roles exist.
    if [ -f "$PGDATA/pg_hba.conf" ] && ! grep -q "host all all 172.0.0.0/8 trust" "$PGDATA/pg_hba.conf"; then
        sed -i '1ihost all all 172.0.0.0/8 trust' "$PGDATA/pg_hba.conf"
    fi

    # Bootstrap required roles via single-user mode (bypasses auth entirely).
    # Errors (e.g. role already exists) are ignored.
    su-exec postgres postgres --single -D "$PGDATA" template1 <<-EOSQL 2>/dev/null || true
        CREATE ROLE postgres LOGIN SUPERUSER PASSWORD '${POSTGRES_PASSWORD}';
        CREATE ROLE ${POSTGRES_USER:-yii2} LOGIN PASSWORD '${POSTGRES_PASSWORD}';
        CREATE DATABASE ${POSTGRES_DB:-keyword_platform} OWNER ${POSTGRES_USER:-yii2};
    EOSQL
fi

exec /usr/local/bin/docker-entrypoint.sh "$@"
