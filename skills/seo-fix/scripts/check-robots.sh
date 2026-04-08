#!/usr/bin/env bash
# Check robots.txt response headers and body
# Usage: scripts/check-robots.sh <BASE_URL>
# Example: scripts/check-robots.sh https://example.com

set -euo pipefail

BASE_URL="${1:?Usage: check-robots.sh <BASE_URL>}"
URL="${BASE_URL%/}/robots.txt"

echo "=== Checking robots.txt at ${URL} ==="
echo ""

echo "--- Response Headers ---"
curl -sI "${URL}" | grep -iE "^(content-type|x-robots-tag|http/)" || true
echo ""

echo "--- Response Body ---"
curl -s "${URL}"
echo ""

echo "--- Validation ---"
BODY=$(curl -s "${URL}")

if echo "${BODY}" | grep -q "^User-agent:"; then
  echo "[OK] User-agent directive found"
else
  echo "[WARN] Missing User-agent directive"
fi

if echo "${BODY}" | grep -q "^Allow: /"; then
  echo "[OK] Allow: / found"
else
  echo "[WARN] Missing Allow: /"
fi

if echo "${BODY}" | grep -qi "^Sitemap:"; then
  echo "[OK] Sitemap directive found"
else
  echo "[WARN] Missing Sitemap directive"
fi
