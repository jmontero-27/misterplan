#!/bin/sh
set -eu

if [ -z "${MYSQL_DATABASE:-}" ]; then
    echo "ERROR: MYSQL_DATABASE is not configured" >&2
    exit 1
fi

if [ -z "${MYSQL_USER:-}" ]; then
    echo "ERROR: MYSQL_USER is not configured" >&2
    exit 1
fi

case "$MYSQL_DATABASE" in
    *_test)
        echo "ERROR: MYSQL_DATABASE must not end with _test" >&2
        exit 1
        ;;
esac

TEST_DATABASE="${MYSQL_DATABASE}_test"

mysql --default-character-set=utf8mb4 \
    -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS \`${TEST_DATABASE}\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON \`${TEST_DATABASE}\`.*
    TO '${MYSQL_USER}'@'%';
SQL

mysql --default-character-set=utf8mb4 \
    -uroot -p"$MYSQL_ROOT_PASSWORD" "$TEST_DATABASE" \
    < /docker-entrypoint-initdb.d/01-schema.sql