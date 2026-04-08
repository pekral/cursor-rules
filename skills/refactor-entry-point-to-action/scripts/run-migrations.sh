#!/usr/bin/env bash
# Run pending database migrations.
# Usage: scripts/run-migrations.sh

set -euo pipefail

echo "=== Running pending migrations ==="
php artisan migrate 2>&1
