#!/usr/bin/env bash
# Check sitemap.xml response headers and body
# Usage: scripts/check-sitemap.sh <BASE_URL>
# Example: scripts/check-sitemap.sh https://example.com

set -euo pipefail

BASE_URL="${1:?Usage: check-sitemap.sh <BASE_URL>}"
URL="${BASE_URL%/}/sitemap.xml"

echo "=== Checking sitemap.xml at ${URL} ==="
echo ""

echo "--- Response Headers ---"
curl -sI "${URL}" | grep -iE "^(content-type|x-robots-tag|http/)" || true
echo ""

echo "--- Response Body ---"
curl -s "${URL}"
echo ""

echo "--- Validation ---"
BODY=$(curl -s "${URL}")

if echo "${BODY}" | grep -q "urlset"; then
  echo "[OK] <urlset> root element found"
else
  echo "[WARN] Missing <urlset> root element"
fi

LOC_COUNT=$(echo "${BODY}" | grep -c "<loc>" || true)
echo "[INFO] Found ${LOC_COUNT} <loc> entries"

if echo "${BODY}" | grep -q "<lastmod>"; then
  echo "[OK] <lastmod> elements found"
else
  echo "[WARN] Missing <lastmod> elements"
fi

if echo "${BODY}" | grep -q "<priority>"; then
  echo "[OK] <priority> elements found"
else
  echo "[WARN] Missing <priority> elements"
fi
