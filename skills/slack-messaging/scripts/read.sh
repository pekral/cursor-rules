#!/usr/bin/env bash
# read.sh — fetch recent messages from a Slack channel via conversations.history.
#
# Usage:
#   read.sh <CHANNEL_ID> [LIMIT]
#
# Arguments:
#   CHANNEL_ID  Slack channel ID, e.g. C0123456789
#   LIMIT       number of latest messages to fetch (default 20, clamped to 1..200)
#
# Auth:
#   Reads a Slack Bot User OAuth Token from SLACK_BOT_TOKEN (xoxb-…).
#   Never read from a file, never written anywhere by this script.
#
# Output:
#   Stable JSON array on stdout, ordered from oldest to newest:
#   [ { "user": <string|null>, "text": <string>, "ts": <string> }, … ]
#
# Exit codes:
#   1  usage / argument error (missing channel, invalid limit)
#   2  missing required tool (curl, jq) or missing SLACK_BOT_TOKEN
#   3  Slack API call failed
set -euo pipefail

PROG="${0##*/}"
# shellcheck source=_lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/_lib.sh"

usage() {
  cat >&2 <<'EOF'
Usage: read.sh <CHANNEL_ID> [LIMIT]

  CHANNEL_ID  Slack channel ID, e.g. C0123456789
  LIMIT       number of latest messages (default 20, max 200)

Auth: export SLACK_BOT_TOKEN with a Slack bot token (xoxb-…).
EOF
}

if [[ $# -lt 1 || -z "${1:-}" ]]; then
  usage
  exit 1
fi

CHANNEL="$1"
LIMIT="${2:-20}"

# Validate LIMIT: must be a positive integer
if ! [[ "$LIMIT" =~ ^[0-9]+$ ]] || [[ "$LIMIT" -lt 1 ]]; then
  echo "${PROG}: LIMIT must be a positive integer (1..200), got: ${LIMIT}" >&2
  usage
  exit 1
fi

# Clamp to 1..200
if [[ "$LIMIT" -gt 200 ]]; then
  LIMIT=200
fi

slack_require_tools
slack_require_token

RESPONSE="$(slack_get "conversations.history" "channel=${CHANNEL}&limit=${LIMIT}")"

# Map to stable shape: [{ user, text, ts }] ordered oldest first (reverse of API order)
printf '%s' "$RESPONSE" | jq '
  [ (.messages // [])[] | { user: (.user // null), text: (.text // ""), ts: (.ts // null) } ]
  | reverse
'
