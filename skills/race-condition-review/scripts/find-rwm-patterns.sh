#!/usr/bin/env bash
# Find potential read-modify-write patterns and concurrency-sensitive code
# Usage: scripts/find-rwm-patterns.sh [directory]
#
# Searches for common race condition signals in PHP code:
# - Direct property mutation followed by save()
# - firstOrCreate / updateOrCreate calls
# - increment / decrement calls
# - lockForUpdate usage
# - Cache read-write patterns

set -euo pipefail

DIR="${1:-.}"

echo "=== Read-Modify-Write patterns (property assignment + save) ==="
grep -rn '->save()' "$DIR" --include='*.php' || true

echo ""
echo "=== firstOrCreate / updateOrCreate calls ==="
grep -rn 'firstOrCreate\|updateOrCreate' "$DIR" --include='*.php' || true

echo ""
echo "=== increment / decrement calls ==="
grep -rn '->increment\|->decrement' "$DIR" --include='*.php' || true

echo ""
echo "=== lockForUpdate usage ==="
grep -rn 'lockForUpdate' "$DIR" --include='*.php' || true

echo ""
echo "=== Cache operations ==="
grep -rn 'Cache::put\|Cache::forget\|Cache::lock\|Cache::get' "$DIR" --include='*.php' || true

echo ""
echo "=== DB::transaction usage ==="
grep -rn 'DB::transaction\|DB::beginTransaction' "$DIR" --include='*.php' || true
