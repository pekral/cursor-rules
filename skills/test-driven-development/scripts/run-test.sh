#!/usr/bin/env bash
# Run a single test file or filter and display results.
# Usage: scripts/run-test.sh [--filter "test name"] [test-file]
#
# Examples:
#   scripts/run-test.sh tests/Feature/OrderTest.php
#   scripts/run-test.sh --filter "rejects empty email"

set -euo pipefail

if [ $# -eq 0 ]; then
    echo "Usage: $0 [--filter \"test name\"] [test-file]"
    exit 1
fi

if command -v php &>/dev/null && [ -f "artisan" ]; then
    php artisan test "$@"
elif command -v vendor/bin/pest &>/dev/null; then
    vendor/bin/pest "$@"
elif command -v vendor/bin/phpunit &>/dev/null; then
    vendor/bin/phpunit "$@"
else
    echo "Error: No test runner found (artisan, pest, phpunit)."
    exit 1
fi
