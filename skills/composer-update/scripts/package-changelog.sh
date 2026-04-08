#!/usr/bin/env bash
# Look up changelog information for a given package.
# Usage: ./package-changelog.sh vendor/package
# Checks vendor directory for CHANGELOG files and prints package source info.

set -euo pipefail

PACKAGE="${1:?Usage: package-changelog.sh vendor/package}"
VENDOR_DIR="vendor/${PACKAGE}"

echo "=== Package Info ==="
composer show "$PACKAGE" 2>&1 || true

echo ""
echo "=== Changelog Files in Vendor ==="
FOUND=0
for FILE in CHANGELOG.md CHANGELOG CHANGES.md HISTORY.md; do
    if [ -f "${VENDOR_DIR}/${FILE}" ]; then
        echo "Found: ${VENDOR_DIR}/${FILE}"
        echo "---"
        head -100 "${VENDOR_DIR}/${FILE}"
        echo "---"
        FOUND=1
        break
    fi
done

if [ "$FOUND" -eq 0 ]; then
    echo "No changelog file found in ${VENDOR_DIR}."
    echo ""
    echo "=== Source URL ==="
    composer show "$PACKAGE" | grep -E '(source|homepage)' || echo "No source URL found."
fi
