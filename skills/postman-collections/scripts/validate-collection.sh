#!/usr/bin/env bash
# Validate Postman collection JSON format and basic structure.
# Usage: ./validate-collection.sh <collection-file>

set -euo pipefail

FILE="${1:?Usage: validate-collection.sh <collection-file>}"

if [ ! -f "$FILE" ]; then
  echo "ERROR: File not found: $FILE"
  exit 1
fi

echo "=== JSON syntax check ==="
if jq . "$FILE" > /dev/null 2>&1; then
  echo "PASS: Valid JSON"
else
  echo "FAIL: Invalid JSON"
  jq . "$FILE" 2>&1 || true
  exit 1
fi

echo ""
echo "=== Schema version ==="
jq -r '.info.schema // "NOT FOUND"' "$FILE"

echo ""
echo "=== Collection name ==="
jq -r '.info.name // "NOT FOUND"' "$FILE"

echo ""
echo "=== Folder count ==="
jq '[.item[] | select(.item)] | length' "$FILE"

echo ""
echo "=== Request count ==="
jq '[.. | select(.request?)] | length' "$FILE"

echo ""
echo "=== Variables ==="
jq '[.variable[]? | .key] // []' "$FILE"

echo ""
echo "=== Hard-coded secrets check ==="
SECRETS=$(jq -r '.. | strings' "$FILE" 2>/dev/null | grep -iE '(bearer [a-z0-9]{20,}|password|secret|sk-|pk_)' || true)
if [ -n "$SECRETS" ]; then
  echo "WARNING: Possible hard-coded secrets detected:"
  echo "$SECRETS" | head -10
else
  echo "PASS: No obvious hard-coded secrets"
fi
