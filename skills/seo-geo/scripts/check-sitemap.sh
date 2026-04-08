#!/usr/bin/env bash
# Usage: scripts/check-sitemap.sh <base-url>
# Fetches sitemap.xml and reports URL count and sample entries.

set -euo pipefail

BASE_URL="${1:?Usage: check-sitemap.sh <base-url>}"
BASE_URL="${BASE_URL%/}"

SITEMAP_URL="${BASE_URL}/sitemap.xml"

echo "=== Fetching sitemap.xml ==="
echo "URL: ${SITEMAP_URL}"
echo ""

CONTENT=$(curl -sL --max-time 10 "${SITEMAP_URL}" 2>&1) || {
  echo "ERROR: Could not fetch ${SITEMAP_URL}"
  exit 1
}

# Check if it looks like valid XML
if ! echo "${CONTENT}" | grep -q "<urlset\|<sitemapindex"; then
  echo "WARNING: Response does not appear to be a valid sitemap."
  echo "First 500 characters:"
  echo "${CONTENT}" | head -c 500
  exit 1
fi

# Count URLs
URL_COUNT=$(echo "${CONTENT}" | grep -c "<loc>" || echo "0")
echo "Total <loc> entries: ${URL_COUNT}"
echo ""

echo "=== Sample URLs (first 10) ==="
echo "${CONTENT}" | grep -oP '(?<=<loc>).*?(?=</loc>)' | head -10

echo ""
echo "=== Sitemap Reference in robots.txt ==="
ROBOTS=$(curl -sL --max-time 10 "${BASE_URL}/robots.txt" 2>/dev/null || echo "")
if echo "${ROBOTS}" | grep -qi "sitemap"; then
  echo "${ROBOTS}" | grep -i "sitemap"
else
  echo "WARNING: No Sitemap directive found in robots.txt"
fi
