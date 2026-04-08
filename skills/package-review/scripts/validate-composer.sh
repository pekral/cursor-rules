#!/usr/bin/env bash
# Validate composer.json structure and required fields.
# Usage: validate-composer.sh [path/to/composer.json]
#
# Checks:
#   - File exists and is valid JSON
#   - Required fields are present: name, description, type, license, authors, require, autoload
#   - Recommended fields are noted if missing: keywords, homepage, support, require-dev, scripts
#   - PSR-4 autoload directories exist

set -euo pipefail

COMPOSER_FILE="${1:-composer.json}"

if [[ ! -f "$COMPOSER_FILE" ]]; then
  echo "ERROR: $COMPOSER_FILE not found"
  exit 1
fi

# Validate JSON syntax
if ! jq empty "$COMPOSER_FILE" 2>/dev/null; then
  echo "ERROR: $COMPOSER_FILE is not valid JSON"
  exit 1
fi

echo "=== Required Fields ==="
REQUIRED_FIELDS=("name" "description" "type" "license" "authors" "require" "autoload")
for field in "${REQUIRED_FIELDS[@]}"; do
  value=$(jq -r ".$field // empty" "$COMPOSER_FILE")
  if [[ -z "$value" ]]; then
    echo "MISSING: $field"
  else
    echo "OK: $field"
  fi
done

echo ""
echo "=== Recommended Fields ==="
RECOMMENDED_FIELDS=("keywords" "homepage" "support" "require-dev" "scripts")
for field in "${RECOMMENDED_FIELDS[@]}"; do
  value=$(jq -r ".\"$field\" // empty" "$COMPOSER_FILE")
  if [[ -z "$value" ]]; then
    echo "MISSING: $field"
  else
    echo "OK: $field"
  fi
done

echo ""
echo "=== PSR-4 Autoload Directories ==="
BASE_DIR=$(dirname "$COMPOSER_FILE")
jq -r '.autoload."psr-4" // {} | to_entries[] | "\(.key) -> \(.value)"' "$COMPOSER_FILE" 2>/dev/null | while IFS= read -r mapping; do
  dir=$(echo "$mapping" | sed 's/.*-> //')
  full_path="$BASE_DIR/$dir"
  if [[ -d "$full_path" ]]; then
    echo "OK: $mapping"
  else
    echo "ERROR: $mapping (directory not found: $full_path)"
  fi
done

echo ""
echo "=== Name Format ==="
name=$(jq -r '.name // ""' "$COMPOSER_FILE")
if [[ "$name" =~ ^[a-z0-9]([a-z0-9_.-]*[a-z0-9])?/[a-z0-9]([a-z0-9_.-]*[a-z0-9])?$ ]]; then
  echo "OK: $name"
else
  echo "WARNING: name '$name' may not follow vendor/package format"
fi
