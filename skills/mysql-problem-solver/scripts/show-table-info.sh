#!/usr/bin/env bash
# Show table structure, columns, and indexes for a given MySQL table.
# Usage: ./show-table-info.sh <table_name>
# Expects DB credentials in environment: DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_DATABASE

set -euo pipefail

TABLE="${1:?Usage: show-table-info.sh <table_name>}"

MYSQL_CMD="mysql -h ${DB_HOST:-127.0.0.1} -P ${DB_PORT:-3306} -u ${DB_USERNAME:-root} -p${DB_PASSWORD:-} ${DB_DATABASE:?DB_DATABASE is required}"

echo "=== SHOW CREATE TABLE $TABLE ==="
$MYSQL_CMD -e "SHOW CREATE TABLE \`$TABLE\`\G"

echo ""
echo "=== DESCRIBE $TABLE ==="
$MYSQL_CMD -e "DESCRIBE \`$TABLE\`;"

echo ""
echo "=== SHOW INDEX FROM $TABLE ==="
$MYSQL_CMD -e "SHOW INDEX FROM \`$TABLE\`;"
