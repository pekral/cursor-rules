#!/usr/bin/env bash
# Check for firstOrCreate/updateOrCreate calls and corresponding unique indexes
# Usage: scripts/check-unique-indexes.sh [directory]
#
# Lists all firstOrCreate and updateOrCreate usages alongside
# unique index definitions in migrations, so you can verify coverage.

set -euo pipefail

DIR="${1:-.}"

echo "=== firstOrCreate / updateOrCreate calls ==="
grep -rn 'firstOrCreate\|updateOrCreate' "$DIR" --include='*.php' | grep -v 'vendor/' || true

echo ""
echo "=== Unique index definitions in migrations ==="
grep -rn '->unique(' "$DIR" --include='*.php' | grep -v 'vendor/' || true

echo ""
echo "=== Unique constraints in migrations ==="
grep -rn 'unique()' "$DIR" --include='*.php' | grep -i 'migrat' || true
