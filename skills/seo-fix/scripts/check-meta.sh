#!/usr/bin/env bash
# Check SEO meta tags on a page
# Usage: scripts/check-meta.sh <PAGE_URL>
# Example: scripts/check-meta.sh https://example.com/pricing

set -euo pipefail

PAGE_URL="${1:?Usage: check-meta.sh <PAGE_URL>}"

echo "=== Checking SEO meta tags at ${PAGE_URL} ==="
echo ""

BODY=$(curl -s "${PAGE_URL}")

echo "--- robots meta ---"
echo "${BODY}" | grep -oi '<meta[^>]*name="robots"[^>]*>' || echo "[WARN] No robots meta tag found"
echo ""

echo "--- canonical ---"
echo "${BODY}" | grep -oi '<link[^>]*rel="canonical"[^>]*>' || echo "[WARN] No canonical link found"
echo ""

echo "--- title ---"
echo "${BODY}" | grep -oi '<title>[^<]*</title>' || echo "[WARN] No title tag found"
echo ""

echo "--- meta description ---"
echo "${BODY}" | grep -oi '<meta[^>]*name="description"[^>]*>' || echo "[WARN] No meta description found"
echo ""

echo "--- sitemap link ---"
echo "${BODY}" | grep -oi '<link[^>]*rel="sitemap"[^>]*>' || echo "[INFO] No sitemap link in head (may be normal for private pages)"
echo ""

echo "--- Open Graph tags ---"
echo "${BODY}" | grep -oi '<meta[^>]*property="og:[^"]*"[^>]*>' || echo "[WARN] No OG tags found"
echo ""
