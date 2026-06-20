#!/usr/bin/env bash
# _lib.sh — shared helpers for the Slack Web API entry points
# (send.sh, read.sh). Sourced, not executed. Keeps the token check, the
# tool check, and the status-checked HTTP helper in one place so a fix
# lands once instead of in every entry script.
#
# Contract for sourcing scripts:
#   - set PROG (used in error messages) before sourcing, or it defaults to $0's basename
#   - call slack_require_tools and slack_require_token once at startup
#   - TOKEN is exported into the caller's scope by slack_require_token
#
# All helpers honor the deterministic exit-code contract:
#   2 = missing tool/token, 3 = Slack API/network failure.
# They never print the token.

set -euo pipefail

API="https://slack.com/api"
: "${PROG:=${0##*/}}"

slack_require_tools() {
  local bin
  for bin in curl jq; do
    if ! command -v "$bin" >/dev/null 2>&1; then
      echo "${PROG}: required tool not found: $bin" >&2
      exit 2
    fi
  done
}

slack_require_token() {
  TOKEN="${SLACK_BOT_TOKEN:-}"
  if [[ -z "$TOKEN" ]]; then
    echo "${PROG}: SLACK_BOT_TOKEN is not set (export a Slack bot token, xoxb-…)" >&2
    exit 2
  fi
}

# slack_check_ok <method> <response-body> -> aborts (exit 3) on a malformed body or
# Slack ok:false. Slack returns HTTP 200 even for logical errors, so .ok must always
# be parsed; a body that is not valid JSON is itself an API failure (e.g. a proxy
# error page). The jq error is surfaced to the project's error sink (stderr), not
# suppressed, and the user sees a generic message rather than a raw parse error.
slack_check_ok() {
  local method="$1" response_body="$2" slack_error
  if ! slack_error="$(printf '%s' "$response_body" | jq -r 'if .ok then empty else .error // "unknown_error" end')"; then
    echo "${PROG}: Slack API returned an unexpected (non-JSON) response for ${method}" >&2
    exit 3
  fi
  if [[ -n "$slack_error" ]]; then
    echo "${PROG}: Slack API error from ${method}: ${slack_error}" >&2
    exit 3
  fi
}

# slack_post <api-method> <json-body> -> echoes response body; aborts on failure.
# Sends a POST request with Authorization: Bearer. Checks HTTP status AND Slack
# ok:false (Slack returns HTTP 200 even for logical errors).
slack_post() {
  local method="$1" body="$2" response http response_body
  response="$(curl -sS -w $'\n%{http_code}' \
    -X POST \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json; charset=utf-8" \
    --data "$body" \
    "${API}/${method}")" || { echo "${PROG}: network error calling ${method}" >&2; exit 3; }
  http="${response##*$'\n'}"
  response_body="${response%$'\n'*}"
  if [[ "$http" -lt 200 || "$http" -ge 300 ]]; then
    echo "${PROG}: Slack API returned HTTP $http for ${method}" >&2
    exit 3
  fi
  slack_check_ok "$method" "$response_body"
  printf '%s' "$response_body"
}

# slack_get <api-method> <query-string> -> echoes response body; aborts on failure.
# Sends a GET request with Authorization: Bearer. Checks HTTP status AND Slack ok:false.
slack_get() {
  local method="$1" query="$2" url response http response_body
  url="${API}/${method}"
  if [[ -n "$query" ]]; then
    url="${url}?${query}"
  fi
  response="$(curl -sS -w $'\n%{http_code}' \
    -H "Authorization: Bearer ${TOKEN}" \
    "$url")" || { echo "${PROG}: network error calling ${method}" >&2; exit 3; }
  http="${response##*$'\n'}"
  response_body="${response%$'\n'*}"
  if [[ "$http" -lt 200 || "$http" -ge 300 ]]; then
    echo "${PROG}: Slack API returned HTTP $http for ${method}" >&2
    exit 3
  fi
  slack_check_ok "$method" "$response_body"
  printf '%s' "$response_body"
}
