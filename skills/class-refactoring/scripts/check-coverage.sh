#!/usr/bin/env bash
# Usage: scripts/check-coverage.sh [filter]
# Runs PHPUnit with coverage for changed files in the current branch.
# Optional filter argument restricts to a specific test class or method.

set -euo pipefail

FILTER="${1:-}"
COVERAGE_DIR="coverage-report"

if [ -n "$FILTER" ]; then
  php artisan test --coverage-html="$COVERAGE_DIR" --filter="$FILTER"
else
  php artisan test --coverage-html="$COVERAGE_DIR"
fi

echo ""
echo "Coverage report generated in $COVERAGE_DIR/"
echo "Review the report then remove it: rm -rf $COVERAGE_DIR/"
