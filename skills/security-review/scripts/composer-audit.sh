#!/usr/bin/env bash
# Run composer audit and check for known vulnerable packages.
# Usage: ./composer-audit.sh [project-root]

set -euo pipefail

DIR="${1:-.}"

echo "=== Composer Security Audit ==="

if [ ! -f "$DIR/composer.lock" ]; then
  echo "No composer.lock found in $DIR. Skipping."
  exit 0
fi

echo ""
echo "--- composer audit ---"
cd "$DIR"
composer audit 2>&1 || echo "composer audit not available or returned findings."

echo ""
echo "--- Check intervention/image version ---"
if grep -q '"name": "intervention/image"' composer.lock; then
  VERSION=$(grep -A1 '"name": "intervention/image"' composer.lock | grep '"version"' | head -1 | sed 's/.*"version": "v\?\([^"]*\)".*/\1/')
  echo "intervention/image version: $VERSION"
  MAJOR=$(echo "$VERSION" | cut -d. -f1)
  if [ "$MAJOR" -lt 3 ] 2>/dev/null; then
    echo "WARNING: intervention/image < v3 has known path traversal risk (Critical)."
  else
    echo "OK: intervention/image >= v3."
  fi
else
  echo "intervention/image not found in dependencies."
fi
