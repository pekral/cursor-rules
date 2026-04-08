#!/usr/bin/env bash
# Check all URLs found in composer.json and README.md for reachability.
# Usage: check-links.sh [project-directory]
#
# Extracts URLs from composer.json fields and README.md, then validates each.

set -euo pipefail

PROJECT_DIR="${1:-.}"
COMPOSER_FILE="$PROJECT_DIR/composer.json"
TIMEOUT=10

check_url() {
  local url="$1"
  local status
  status=$(curl -o /dev/null -s -w "%{http_code}" --max-time "$TIMEOUT" -L "$url" 2>/dev/null || echo "000")
  if [[ "$status" =~ ^2 ]]; then
    echo "OK ($status): $url"
  elif [[ "$status" =~ ^3 ]]; then
    echo "REDIRECT ($status): $url"
  elif [[ "$status" == "000" ]]; then
    echo "UNREACHABLE: $url"
  else
    echo "BROKEN ($status): $url"
  fi
}

URLS=()

# Extract URLs from composer.json
if [[ -f "$COMPOSER_FILE" ]]; then
  while IFS= read -r url; do
    [[ -n "$url" ]] && URLS+=("$url")
  done < <(jq -r '
    [.homepage, .support.issues, .support.source, .support.docs, .support.wiki, .support.forum] +
    [.authors[]?.homepage // empty] | map(select(. != null and . != "")) | .[]
  ' "$COMPOSER_FILE" 2>/dev/null)
fi

# Extract URLs from README.md
README_FILE="$PROJECT_DIR/README.md"
if [[ -f "$README_FILE" ]]; then
  while IFS= read -r url; do
    [[ -n "$url" ]] && URLS+=("$url")
  done < <(grep -oP 'https?://[^\s\)\]>"]+' "$README_FILE" 2>/dev/null || true)
fi

# Deduplicate
UNIQUE_URLS=($(printf '%s\n' "${URLS[@]}" | sort -u))

if [[ ${#UNIQUE_URLS[@]} -eq 0 ]]; then
  echo "No URLs found to check."
  exit 0
fi

echo "=== Link Validation (${#UNIQUE_URLS[@]} URLs) ==="
for url in "${UNIQUE_URLS[@]}"; do
  check_url "$url"
done
