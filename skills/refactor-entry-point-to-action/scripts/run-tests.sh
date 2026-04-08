#!/usr/bin/env bash
# Run tests related to the refactored code.
# Usage: scripts/run-tests.sh [test-file-or-filter...]
# If no arguments given, runs the full test suite.

set -euo pipefail

if [ $# -gt 0 ]; then
  echo "=== Running targeted tests ==="
  php artisan test "$@" 2>&1
else
  echo "=== Running full test suite ==="
  php artisan test 2>&1
fi
