#!/usr/bin/env bash
# Run EXPLAIN on a SQL query against the configured MySQL database.
# Usage: ./run-explain.sh "<SELECT query>" [format]
# format: traditional (default), json
# Expects DB credentials in environment: DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_DATABASE

set -euo pipefail

QUERY="${1:?Usage: run-explain.sh \"<SELECT query>\" [format]}"
FORMAT="${2:-traditional}"

MYSQL_CMD="mysql -h ${DB_HOST:-127.0.0.1} -P ${DB_PORT:-3306} -u ${DB_USERNAME:-root} -p${DB_PASSWORD:-} ${DB_DATABASE:?DB_DATABASE is required}"

case "$FORMAT" in
  json)
    echo "=== EXPLAIN FORMAT=JSON ==="
    $MYSQL_CMD -e "EXPLAIN FORMAT=JSON $QUERY"
    ;;
  *)
    echo "=== EXPLAIN ==="
    $MYSQL_CMD -e "EXPLAIN $QUERY"
    ;;
esac
