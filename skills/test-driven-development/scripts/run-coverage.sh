#!/usr/bin/env bash
# Run tests with code coverage and display summary.
# Usage: scripts/run-coverage.sh [test-file]
#
# Examples:
#   scripts/run-coverage.sh
#   scripts/run-coverage.sh tests/Feature/OrderTest.php

set -euo pipefail

ARGS=("$@")

if command -v php &>/dev/null && [ -f "artisan" ]; then
    php artisan test --coverage "${ARGS[@]}"
elif command -v vendor/bin/pest &>/dev/null; then
    vendor/bin/pest --coverage "${ARGS[@]}"
elif command -v vendor/bin/phpunit &>/dev/null; then
    vendor/bin/phpunit --coverage-text "${ARGS[@]}"
else
    echo "Error: No test runner found (artisan, pest, phpunit)."
    exit 1
fi
