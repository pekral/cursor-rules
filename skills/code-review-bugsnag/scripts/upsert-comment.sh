#!/usr/bin/env bash
# upsert-comment.sh — append-only Bugsnag error comment publisher used by
# CR-track skills. Every invocation POSTs a fresh comment via the Data Access
# API, mirroring code-review-github/scripts/upsert-comment.sh: each CR run owns
# its own self-contained entry instead of editing an earlier one in place.
# Bugsnag renders comment bodies as plain text (no hidden HTML markers), so —
# unlike the GitHub publisher — no invisible per-actor marker is appended; the
# authenticated token already identifies the author.
#
# Usage:
#   upsert-comment.sh <URL|ORG/PROJECT/ERROR_ID> <BODY_FILE>
#   <body-producer> | upsert-comment.sh <URL|ORG/PROJECT/ERROR_ID> -
#
# Inputs:
#   URL|TRIPLE  an app.bugsnag.com error URL, or <org-slug>/<project-slug>/<error-id>
#   BODY_FILE   path to a file holding the comment body, or `-` to read stdin.
#
# Auth:
#   Reads a Data Access API token from BUGSNAG_TOKEN (BUGSNAG_AUTH_TOKEN alias).
#   Never read from a file, never written anywhere by this script.
#
# Output:
#   The created comment id on stdout. `action=created` on stderr for the calling
#   skill to log in its summary line.
#
# Exit codes:
#   1  usage / argument error
#   2  missing required tool (curl, jq) or missing BUGSNAG_TOKEN
#   3  Bugsnag API call failed
set -euo pipefail

API="https://api.bugsnag.com"

usage() {
  cat >&2 <<'EOF'
Usage: upsert-comment.sh <URL|ORG/PROJECT/ERROR_ID> <BODY_FILE|->

  URL|TRIPLE  app.bugsnag.com error URL, or <org-slug>/<project-slug>/<error-id>
  BODY_FILE   path to a file containing the comment body, or `-` for stdin

Auth: export BUGSNAG_TOKEN with a Data Access API token.
EOF
}

if [[ $# -ne 2 || -z "${1:-}" || -z "${2:-}" ]]; then
  usage
  exit 1
fi

INPUT="$1"
BODY_SRC="$2"

for bin in curl jq; do
  if ! command -v "$bin" >/dev/null 2>&1; then
    echo "upsert-comment.sh: required tool not found: $bin" >&2
    exit 2
  fi
done

TOKEN="${BUGSNAG_TOKEN:-${BUGSNAG_AUTH_TOKEN:-}}"
if [[ -z "$TOKEN" ]]; then
  echo "upsert-comment.sh: BUGSNAG_TOKEN is not set (export a Data Access API token)" >&2
  exit 2
fi

# --- parse org slug / project slug / error id (same grammar as load-issue.sh) ---
if [[ "$INPUT" =~ ^https?://(www\.)?app\.bugsnag\.com/ ]]; then
  parsed="$(printf '%s' "$INPUT" | sed -nE 's#^https?://(www\.)?app\.bugsnag\.com/([^/]+)/([^/]+)/errors/([0-9a-fA-F]+).*#\2 \3 \4#p')"
elif [[ "$INPUT" =~ ^[^/]+/[^/]+/[0-9a-fA-F]+$ ]]; then
  parsed="$(printf '%s' "$INPUT" | awk -F/ '{print $1, $2, $3}')"
else
  echo "upsert-comment.sh: argument must be an app.bugsnag.com URL or <org>/<project>/<error-id>: $INPUT" >&2
  exit 1
fi
if [[ -z "${parsed:-}" ]]; then
  echo "upsert-comment.sh: could not extract org/project/error from input: $INPUT" >&2
  exit 1
fi
ORG_SLUG="$(printf '%s' "$parsed" | awk '{print $1}')"
PROJ_SLUG="$(printf '%s' "$parsed" | awk '{print $2}')"
ERROR_ID="$(printf '%s' "$parsed" | awk '{print $3}')"

if [[ "$BODY_SRC" == "-" ]]; then
  BODY="$(cat)"
else
  if [[ ! -r "$BODY_SRC" ]]; then
    echo "upsert-comment.sh: cannot read body file: $BODY_SRC" >&2
    exit 1
  fi
  BODY="$(cat "$BODY_SRC")"
fi
if [[ -z "$BODY" ]]; then
  echo "upsert-comment.sh: refusing to publish an empty comment" >&2
  exit 1
fi

bsnag_get() {
  local url="$1" body http
  body="$(curl -sS -w $'\n%{http_code}' \
    -H "Authorization: token ${TOKEN}" -H "X-Version: 2" "$url")" \
    || { echo "upsert-comment.sh: network error calling $url" >&2; exit 3; }
  http="${body##*$'\n'}"; body="${body%$'\n'*}"
  if [[ "$http" -lt 200 || "$http" -ge 300 ]]; then
    echo "upsert-comment.sh: Bugsnag API returned HTTP $http for $url" >&2
    exit 3
  fi
  printf '%s' "$body"
}

# --- resolve org id -> project id (slugs are not API keys) ------------------
ORG_ID="$(bsnag_get "${API}/user/organizations" | jq -r --arg s "$ORG_SLUG" 'map(select(.slug == $s)) | .[0].id // empty')"
if [[ -z "$ORG_ID" ]]; then
  echo "upsert-comment.sh: organization slug not found or not accessible: $ORG_SLUG" >&2
  exit 3
fi

PROJ_ID=""
next="${API}/organizations/${ORG_ID}/projects?per_page=100&sort=created_at&direction=asc"
pages=0
while [[ -n "$next" && "$pages" -lt 30 ]]; do
  pages=$((pages + 1))
  headers="$(mktemp)"
  page_body="$(curl -sS -D "$headers" -H "Authorization: token ${TOKEN}" -H "X-Version: 2" "$next")" \
    || { rm -f "$headers"; echo "upsert-comment.sh: network error listing projects" >&2; exit 3; }
  id="$(printf '%s' "$page_body" | jq -r --arg s "$PROJ_SLUG" 'map(select(.slug == $s)) | .[0].id // empty' 2>/dev/null || true)"
  if [[ -n "$id" ]]; then PROJ_ID="$id"; rm -f "$headers"; break; fi
  next="$(grep -i '^link:' "$headers" | sed -nE 's/.*<([^>]+)>; *rel="next".*/\1/p' || true)"
  rm -f "$headers"
done
if [[ -z "$PROJ_ID" ]]; then
  echo "upsert-comment.sh: project slug not found in organization: $PROJ_SLUG" >&2
  exit 3
fi

# --- POST a fresh comment ---------------------------------------------------
RESPONSE="$(jq -n --arg message "$BODY" '{message:$message}' | curl -sS -w $'\n%{http_code}' \
  -X POST \
  -H "Authorization: token ${TOKEN}" -H "X-Version: 2" -H "Content-Type: application/json" \
  --data @- \
  "${API}/projects/${PROJ_ID}/errors/${ERROR_ID}/comments")"
HTTP="${RESPONSE##*$'\n'}"
BODY_OUT="${RESPONSE%$'\n'*}"
if [[ "$HTTP" -lt 200 || "$HTTP" -ge 300 ]]; then
  echo "upsert-comment.sh: comment POST failed (HTTP $HTTP) on ${ORG_SLUG}/${PROJ_SLUG}/${ERROR_ID}" >&2
  exit 3
fi
NEW_ID="$(printf '%s' "$BODY_OUT" | jq -r '.id // empty')"
printf '%s\n' "$NEW_ID"
echo "action=created id=${NEW_ID}" >&2
