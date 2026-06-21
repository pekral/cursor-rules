#!/usr/bin/env bash
# send.sh — post a message to a Slack channel via chat.postMessage.
#
# Usage:
#   send.sh <CHANNEL_ID> <TEXT|->
#
# Arguments:
#   CHANNEL_ID  Slack channel ID, e.g. C0123456789
#   TEXT        message text, or `-` to read the text from stdin
#
# Auth:
#   Reads a Slack Bot User OAuth Token from SLACK_BOT_TOKEN (xoxb-…).
#   Never read from a file, never written anywhere by this script.
#
# Output:
#   The `ts` of the sent message on stdout.
#   `action=sent channel=<id> ts=<ts>` on stderr for the calling skill to log.
#
# Exit codes:
#   1  usage / argument error (missing argument, empty text)
#   2  missing required tool (curl, jq) or missing SLACK_BOT_TOKEN
#   3  Slack API call failed
set -euo pipefail

PROG="${0##*/}"
# shellcheck source=_lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/_lib.sh"

usage() {
  cat >&2 <<'EOF'
Usage: send.sh <CHANNEL_ID> <TEXT|->

  CHANNEL_ID  Slack channel ID, e.g. C0123456789
  TEXT        message text, or `-` to read the text from stdin

Auth: export SLACK_BOT_TOKEN with a Slack bot token (xoxb-…).
EOF
}

if [[ $# -ne 2 || -z "${1:-}" || -z "${2:-}" ]]; then
  usage
  exit 1
fi

CHANNEL="$1"
TEXT_SRC="$2"

slack_require_tools
slack_require_token

if [[ "$TEXT_SRC" == "-" ]]; then
  TEXT="$(cat)"
else
  TEXT="$TEXT_SRC"
fi

if [[ -z "$TEXT" ]]; then
  echo "${PROG}: refusing to send an empty message" >&2
  exit 1
fi

BODY="$(jq -n --arg channel "$CHANNEL" --arg text "$TEXT" '{channel: $channel, text: $text}')"
RESPONSE="$(slack_post "chat.postMessage" "$BODY")"
TS="$(printf '%s' "$RESPONSE" | jq -r '.ts // empty')"

if [[ -z "$TS" ]]; then
  echo "${PROG}: chat.postMessage succeeded but returned no ts" >&2
  exit 3
fi

printf '%s\n' "$TS"
echo "action=sent channel=${CHANNEL} ts=${TS}" >&2
