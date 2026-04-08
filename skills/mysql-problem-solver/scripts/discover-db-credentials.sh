#!/usr/bin/env bash
# Discover MySQL credentials from common project config files.
# Usage: ./discover-db-credentials.sh [project_root]
# Prints DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_DATABASE if found.

set -euo pipefail

ROOT="${1:-.}"

# Try .env file first
if [[ -f "$ROOT/.env" ]]; then
  echo "=== Found .env ==="
  grep -E '^DB_(HOST|PORT|USERNAME|PASSWORD|DATABASE|CONNECTION)=' "$ROOT/.env" 2>/dev/null || true
fi

# Try docker-compose files
for f in "$ROOT/docker-compose.yml" "$ROOT/docker-compose.yaml" "$ROOT/docker-compose.override.yml"; do
  if [[ -f "$f" ]]; then
    echo "=== Found $f ==="
    grep -iE '(MYSQL_ROOT_PASSWORD|MYSQL_DATABASE|MYSQL_USER|MYSQL_PASSWORD|ports)' "$f" 2>/dev/null || true
  fi
done

# Try Laravel config
if [[ -f "$ROOT/config/database.php" ]]; then
  echo "=== Found config/database.php ==="
  grep -E "(host|port|database|username|password)" "$ROOT/config/database.php" 2>/dev/null || true
fi
