#!/usr/bin/env bash
# Run tests only for files changed in the current branch (compared to main).
# Usage: ./run-changed-tests.sh [BASE_BRANCH]
# Default base branch: main

set -euo pipefail

BASE="${1:-main}"

echo "=== Finding changed test files (vs $BASE) ==="

# Get changed PHP files in test directories
CHANGED_TESTS=$(git diff --name-only "$BASE"...HEAD -- '*.php' | grep -iE '(test|spec)' || true)

if [ -z "$CHANGED_TESTS" ]; then
  echo "No test files changed directly."
  echo ""
  echo "Looking for tests related to changed source files..."

  CHANGED_SRC=$(git diff --name-only "$BASE"...HEAD -- '*.php' | grep -v -iE '(test|spec)' || true)

  if [ -z "$CHANGED_SRC" ]; then
    echo "No PHP files changed."
    exit 0
  fi

  echo "Changed source files:"
  echo "$CHANGED_SRC"
  echo ""
  echo "Derive corresponding test files and run them with your project's test runner."
else
  echo "Changed test files:"
  echo "$CHANGED_TESTS"
  echo ""
  echo "Run these with your project's test runner (e.g., phpunit --filter or pest --filter)."
fi
