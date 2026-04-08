#!/usr/bin/env bash
# Usage: scripts/check-meta-tags.sh <url>
# Fetches a page and extracts key SEO/GEO meta information.

set -euo pipefail

URL="${1:?Usage: check-meta-tags.sh <url>}"

echo "=== Fetching page ==="
echo "URL: ${URL}"
echo ""

HTML=$(curl -sL --max-time 15 "${URL}" 2>&1) || {
  echo "ERROR: Could not fetch ${URL}"
  exit 1
}

echo "=== Title ==="
TITLE=$(echo "${HTML}" | grep -oP '(?<=<title>).*?(?=</title>)' | head -1 || echo "NOT FOUND")
echo "  ${TITLE:-NOT FOUND}"
TITLE_LEN=${#TITLE}
echo "  Length: ${TITLE_LEN} characters"
echo ""

echo "=== Meta Description ==="
DESC=$(echo "${HTML}" | grep -oiP '<meta\s+name="description"\s+content="[^"]*"' | grep -oP 'content="\K[^"]*' | head -1 || echo "NOT FOUND")
echo "  ${DESC:-NOT FOUND}"
DESC_LEN=${#DESC}
echo "  Length: ${DESC_LEN} characters"
echo ""

echo "=== Open Graph Tags ==="
for TAG in og:title og:description og:image og:url og:type; do
  VALUE=$(echo "${HTML}" | grep -oiP "<meta\s+property=\"${TAG}\"\s+content=\"[^\"]*\"" | grep -oP 'content="\K[^"]*' | head -1 || echo "")
  if [ -n "${VALUE}" ]; then
    echo "  ${TAG}: ${VALUE}"
  else
    echo "  ${TAG}: NOT FOUND"
  fi
done
echo ""

echo "=== JSON-LD Structured Data ==="
JSONLD_COUNT=$(echo "${HTML}" | grep -c 'application/ld+json' || echo "0")
if [ "${JSONLD_COUNT}" -gt 0 ]; then
  echo "  Found ${JSONLD_COUNT} JSON-LD block(s)"
  echo "${HTML}" | grep -oP '(?<=<script type="application/ld\+json">).*?(?=</script>)' | head -3
else
  echo "  NOT FOUND"
fi
echo ""

echo "=== Canonical ==="
CANONICAL=$(echo "${HTML}" | grep -oiP '<link\s+rel="canonical"\s+href="[^"]*"' | grep -oP 'href="\K[^"]*' | head -1 || echo "NOT FOUND")
echo "  ${CANONICAL:-NOT FOUND}"
