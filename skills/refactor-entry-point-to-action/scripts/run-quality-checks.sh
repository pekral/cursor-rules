#!/usr/bin/env bash
# Run project quality checks (lint, static analysis, code style) on changed PHP files.
# Usage: scripts/run-quality-checks.sh [file...]
# If no files given, runs on all staged/modified PHP files.

set -euo pipefail

if [ $# -gt 0 ]; then
  FILES="$@"
else
  FILES=$(git diff --name-only --diff-filter=ACMR HEAD -- '*.php' 2>/dev/null || true)
  if [ -z "$FILES" ]; then
    echo "No changed PHP files found."
    exit 0
  fi
fi

echo "=== Running PHP CS Fixer ==="
if [ -f vendor/bin/php-cs-fixer ]; then
  vendor/bin/php-cs-fixer fix --dry-run --diff $FILES 2>&1 || true
elif [ -f vendor/bin/pint ]; then
  vendor/bin/pint --test $FILES 2>&1 || true
else
  echo "No code style fixer found (php-cs-fixer or pint). Skipping."
fi

echo ""
echo "=== Running PHPStan ==="
if [ -f vendor/bin/phpstan ]; then
  vendor/bin/phpstan analyse $FILES 2>&1
else
  echo "PHPStan not found. Skipping."
fi
