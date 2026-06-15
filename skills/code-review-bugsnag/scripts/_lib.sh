#!/usr/bin/env bash
# _lib.sh — shared helpers for the Bugsnag Data Access API entry points
# (load-issue.sh, upsert-comment.sh). Sourced, not executed. Keeps the URL/triple
# parse grammar, the status-checked HTTP helper, and the slug→id resolution in one
# place so a fix lands once instead of in every entry script.
#
# Contract for sourcing scripts:
#   - set PROG (used in error messages) before sourcing, or it defaults to $0's basename
#   - call bsnag_require_tools and bsnag_require_token once at startup
#   - TOKEN is exported into the caller's scope by bsnag_require_token
#
# All helpers honor the deterministic exit-code contract: 2 = missing tool/token,
# 3 = Bugsnag API/network failure. They never print the token.

API="https://api.bugsnag.com"
: "${PROG:=${0##*/}}"

bsnag_require_tools() {
  local bin
  for bin in curl jq; do
    if ! command -v "$bin" >/dev/null 2>&1; then
      echo "${PROG}: required tool not found: $bin" >&2
      exit 2
    fi
  done
}

bsnag_require_token() {
  TOKEN="${BUGSNAG_TOKEN:-${BUGSNAG_AUTH_TOKEN:-}}"
  if [[ -z "$TOKEN" ]]; then
    echo "${PROG}: BUGSNAG_TOKEN is not set (export a Data Access API token)" >&2
    exit 2
  fi
}

# bsnag_parse_ref <input> -> echoes "<org-slug> <project-slug> <error-id>" or returns 1.
# Accepts an app.bugsnag.com error URL or an <org>/<project>/<error-id> triple.
bsnag_parse_ref() {
  local input="$1" parsed
  if [[ "$input" =~ ^https?://(www\.)?app\.bugsnag\.com/ ]]; then
    parsed="$(printf '%s' "$input" | sed -nE 's#^https?://(www\.)?app\.bugsnag\.com/([^/]+)/([^/]+)/errors/([0-9a-fA-F]+).*#\2 \3 \4#p')"
  elif [[ "$input" =~ ^[^/]+/[^/]+/[0-9a-fA-F]+$ ]]; then
    parsed="$(printf '%s' "$input" | awk -F/ '{print $1, $2, $3}')"
  else
    echo "${PROG}: argument must be an app.bugsnag.com URL or <org>/<project>/<error-id>: $input" >&2
    return 1
  fi
  if [[ -z "$parsed" ]]; then
    echo "${PROG}: could not extract org/project/error from input: $input" >&2
    return 1
  fi
  printf '%s' "$parsed"
}

# bsnag_get <url> -> echoes the response body; aborts with exit 3 on any non-2xx.
bsnag_get() {
  local url="$1" body http
  body="$(curl -sS -w $'\n%{http_code}' \
    -H "Authorization: token ${TOKEN}" \
    -H "X-Version: 2" \
    -H "Content-Type: application/json" \
    "$url")" || { echo "${PROG}: network error calling $url" >&2; exit 3; }
  http="${body##*$'\n'}"
  body="${body%$'\n'*}"
  if [[ "$http" -lt 200 || "$http" -ge 300 ]]; then
    echo "${PROG}: Bugsnag API returned HTTP $http for $url" >&2
    exit 3
  fi
  printf '%s' "$body"
}

# bsnag_resolve_org_id <org-slug> -> echoes the numeric org id; aborts on failure.
bsnag_resolve_org_id() {
  local org_slug="$1" id
  id="$(bsnag_get "${API}/user/organizations" | jq -r --arg s "$org_slug" 'map(select(.slug == $s)) | .[0].id // empty')"
  if [[ -z "$id" ]]; then
    echo "${PROG}: organization slug not found or not accessible: $org_slug" >&2
    exit 3
  fi
  printf '%s' "$id"
}

# bsnag_resolve_project_json <org-id> <project-slug> -> echoes the matching project
# JSON object; aborts on failure. The pagination loop is status-checked (a transient
# 429/500 or expired token surfaces the real HTTP cause instead of a misleading
# "slug not found"), and a hit on the page cap is reported distinctly rather than
# silently collapsing into "not found".
bsnag_resolve_project_json() {
  local org_id="$1" proj_slug="$2"
  local next="${API}/organizations/${org_id}/projects?per_page=100&sort=created_at&direction=asc"
  local pages=0 headers page http match
  while [[ -n "$next" && "$pages" -lt 30 ]]; do
    pages=$((pages + 1))
    headers="$(mktemp)"
    page="$(curl -sS -w $'\n%{http_code}' -D "$headers" \
      -H "Authorization: token ${TOKEN}" -H "X-Version: 2" "$next")" \
      || { rm -f "$headers"; echo "${PROG}: network error listing projects" >&2; exit 3; }
    http="${page##*$'\n'}"
    page="${page%$'\n'*}"
    if [[ "$http" -lt 200 || "$http" -ge 300 ]]; then
      rm -f "$headers"
      echo "${PROG}: Bugsnag API returned HTTP $http listing projects" >&2
      exit 3
    fi
    match="$(printf '%s' "$page" | jq -c --arg s "$proj_slug" 'map(select(.slug == $s)) | .[0] // empty' 2>/dev/null || true)"
    if [[ -n "$match" ]]; then
      rm -f "$headers"
      printf '%s' "$match"
      return 0
    fi
    next="$(grep -i '^link:' "$headers" | sed -nE 's/.*<([^>]+)>; *rel="next".*/\1/p' || true)"
    rm -f "$headers"
  done
  if [[ "$pages" -ge 30 && -n "$next" ]]; then
    echo "${PROG}: stopped after 30 pages (3000 projects) without finding slug: $proj_slug" >&2
  else
    echo "${PROG}: project slug not found in organization: $proj_slug" >&2
  fi
  exit 3
}
