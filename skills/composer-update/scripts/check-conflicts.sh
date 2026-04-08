#!/usr/bin/env bash
# Check for dependency conflicts after a composer update.
# Usage: ./check-conflicts.sh [vendor/package version]
# Without arguments: runs composer validate and checks for general issues.
# With arguments: runs composer why-not for a specific package version.

set -euo pipefail

if [ $# -ge 2 ]; then
    echo "=== Why-Not Check: $1 $2 ==="
    composer why-not "$1" "$2" 2>&1 || true
else
    echo "=== Composer Validate ==="
    composer validate --no-check-publish 2>&1 || true

    echo ""
    echo "=== Composer Audit ==="
    composer audit 2>&1 || true
fi
