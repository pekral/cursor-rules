#!/usr/bin/env bash
# Detect changed API endpoints from the current branch diff.
# Looks at route files, controllers, and OpenAPI schemas.
# Usage: ./detect-changed-endpoints.sh [base-branch]

set -euo pipefail

BASE="${1:-main}"

echo "=== Changed route files ==="
git diff "$BASE"...HEAD --name-only -- \
  '*/routes/*' \
  '*/routes.php' \
  '*/api.php' \
  '*/web.php' \
  '*router*' \
  2>/dev/null || true

echo ""
echo "=== Changed controllers ==="
git diff "$BASE"...HEAD --name-only -- \
  '*Controller*' \
  '*controller*' \
  2>/dev/null || true

echo ""
echo "=== Changed request/DTO classes ==="
git diff "$BASE"...HEAD --name-only -- \
  '*Request*' \
  '*request*' \
  '*Dto*' \
  '*dto*' \
  2>/dev/null || true

echo ""
echo "=== Changed OpenAPI/Swagger files ==="
git diff "$BASE"...HEAD --name-only -- \
  '*openapi*' \
  '*swagger*' \
  '*.yaml' \
  '*.yml' \
  2>/dev/null | grep -iE '(openapi|swagger|api)' || true

echo ""
echo "=== Full diff of route files ==="
git diff "$BASE"...HEAD -- \
  '*/routes/*' \
  '*/routes.php' \
  '*/api.php' \
  '*/web.php' \
  2>/dev/null || true
