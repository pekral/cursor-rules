#!/usr/bin/env bash
# Usage: scripts/check-robots.sh <base-url>
# Fetches robots.txt and checks AI bot access policy.

set -euo pipefail

BASE_URL="${1:?Usage: check-robots.sh <base-url>}"
BASE_URL="${BASE_URL%/}"

ROBOTS_URL="${BASE_URL}/robots.txt"

echo "=== Fetching robots.txt ==="
echo "URL: ${ROBOTS_URL}"
echo ""

CONTENT=$(curl -sL --max-time 10 "${ROBOTS_URL}" 2>&1) || {
  echo "ERROR: Could not fetch ${ROBOTS_URL}"
  exit 1
}

echo "${CONTENT}"
echo ""
echo "=== AI Bot Access Check ==="

BOTS=("Googlebot" "Bingbot" "GPTBot" "ChatGPT-User" "Perplexity-User" "ClaudeBot" "anthropic-ai")

for BOT in "${BOTS[@]}"; do
  if echo "${CONTENT}" | grep -qi "User-agent:.*${BOT}"; then
    RULES=$(echo "${CONTENT}" | grep -iA5 "User-agent:.*${BOT}" | grep -i "disallow" || true)
    if [ -n "${RULES}" ]; then
      echo "  ${BOT}: RESTRICTED"
      echo "    ${RULES}"
    else
      echo "  ${BOT}: ALLOWED (specific rules, no disallow)"
    fi
  else
    echo "  ${BOT}: ALLOWED (no specific rules, falls under default)"
  fi
done
