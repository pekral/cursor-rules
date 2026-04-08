#!/usr/bin/env bash
# Detect which packages were updated by running a dry-run composer update.
# Usage: ./detect-updates.sh
# If composer.lock is committed, you can also diff it against HEAD to see changes.

set -euo pipefail

echo "=== Composer Update Dry Run ==="
composer update --dry-run 2>&1

echo ""
echo "=== Lock File Diff (if committed) ==="
if git diff HEAD -- composer.lock >/dev/null 2>&1; then
    git diff HEAD -- composer.lock | grep -E '^\s+"name":' || echo "No lock file changes detected."
else
    echo "composer.lock is not tracked by git or no changes found."
fi
