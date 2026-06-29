#!/usr/bin/env bash
# download-attachments.sh — download every attachment of a JIRA issue (and its
# subtasks) into a quarantine directory and hand them to the mandatory security scan.
#
# Usage:
#   download-attachments.sh <KEY|URL> [--dest DIR]
#
# Auth (JIRA Cloud uses HTTP Basic `email:token`):
#   - API token, resolved in this order (first hit wins):
#       1. --token-file FILE   (file holds the token, or an `email:token` line)
#       2. env JIRA_API_TOKEN
#       3. ~/.config/acli/jira_api_token   (chmod 600)
#   - Account email: env JIRA_API_EMAIL, or the `email:token` form of the token file.
#   The token is never passed in argv and never printed; it is written only into a
#   0600 curl --config file. Without a usable token the script exits non-zero with a
#   setup hint — it never falls back to an unauthenticated or silent attempt.
#
# Attachment endpoint: GET https://<site>/rest/api/3/attachment/content/<id>
# (taken verbatim from each attachment's contentUrl in load-issue.sh).
#
# Exit codes:
#   1  usage error
#   2  missing tool / missing or unresolvable auth
#   3  inventory load or download failure
set -euo pipefail

PROG="download-attachments.sh"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SHARED_DIR="${SCRIPT_DIR}/../../_shared"

usage() {
  cat >&2 <<'EOF'
Usage: download-attachments.sh <KEY|URL> [--dest DIR]

  KEY|URL   JIRA issue key (e.g. ECOMAIL-1234) or a /browse/<KEY> URL
  --dest    quarantine root (default: $CLAUDE_SCRATCHPAD_DIR/attachments)

Auth:
  JIRA_API_EMAIL   account email for HTTP Basic auth
  JIRA_API_TOKEN   API token (or use --token-file / ~/.config/acli/jira_api_token)
  --token-file F   file holding the token, or an `email:token` line
EOF
}

REF=""
DEST=""
TOKEN_FILE=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --dest) DEST="${2:-}"; shift 2;;
    --token-file) TOKEN_FILE="${2:-}"; shift 2;;
    -h|--help) usage; exit 1;;
    -*) echo "${PROG}: unknown option: $1" >&2; usage; exit 1;;
    *) if [[ -z "$REF" ]]; then REF="$1"; shift; else echo "${PROG}: unexpected argument: $1" >&2; exit 1; fi;;
  esac
done

if [[ -z "$REF" ]]; then usage; exit 1; fi

# shellcheck source=../../_shared/attachments.sh
. "${SHARED_DIR}/attachments.sh"
att_require_tools

[[ -z "$DEST" ]] && DEST="$(att_default_dest)/jira"

# --- resolve auth token + email without ever exposing them in argv/logs ---
resolve_token() {
  if [[ -n "$TOKEN_FILE" ]]; then
    [[ -r "$TOKEN_FILE" ]] || { echo "${PROG}: --token-file not readable: $TOKEN_FILE" >&2; exit 2; }
    head -n1 "$TOKEN_FILE"
    return 0
  fi
  if [[ -n "${JIRA_API_TOKEN:-}" ]]; then
    printf '%s' "$JIRA_API_TOKEN"
    return 0
  fi
  local default="${HOME}/.config/acli/jira_api_token"
  if [[ -r "$default" ]]; then
    head -n1 "$default"
    return 0
  fi
  return 1
}

RAW_TOKEN="$(resolve_token || true)"
if [[ -z "$RAW_TOKEN" ]]; then
  echo "${PROG}: no JIRA API token found." >&2
  echo "  Provide one via --token-file FILE, JIRA_API_TOKEN, or ~/.config/acli/jira_api_token (chmod 600)." >&2
  echo "  Create a token at https://id.atlassian.com/manage-profile/security/api-tokens" >&2
  exit 2
fi

EMAIL="${JIRA_API_EMAIL:-}"
TOKEN="$RAW_TOKEN"
# Accept the `email:token` convenience form from the token source.
if [[ -z "$EMAIL" && "$RAW_TOKEN" == *:* ]]; then
  EMAIL="${RAW_TOKEN%%:*}"
  TOKEN="${RAW_TOKEN#*:}"
fi
if [[ -z "$EMAIL" ]]; then
  echo "${PROG}: account email not set. Export JIRA_API_EMAIL or use an 'email:token' token file." >&2
  exit 2
fi

# --- load the attachment inventory via the deterministic loader (issue + subtasks) ---
# stderr suppressed: the loader's own diagnostics are noise; its result is validated on the next line.
ISSUE_JSON="$("${SCRIPT_DIR}/load-issue.sh" "$REF" 2>/dev/null || true)"
if [[ -z "$ISSUE_JSON" ]] || ! printf '%s' "$ISSUE_JSON" | jq -e . >/dev/null 2>&1; then
  echo "${PROG}: failed to load JIRA issue inventory for: $REF" >&2
  exit 3
fi

INVENTORY="$(printf '%s' "$ISSUE_JSON" | jq -c '
  [ (.attachments // [])[], ((.subtasks // [])[] | (.attachments // [])[]) ]
  | map({ id: (.id|tostring), name: .name, declaredMime: .mimeType, size: .size, contentUrl: .contentUrl })
')"

COUNT="$(printf '%s' "$INVENTORY" | jq 'length')"
if [[ "$COUNT" -eq 0 ]]; then
  echo "${PROG}: no attachments on $REF (nothing to download)." >&2
fi

# --- write the 0600 curl auth config (token stays out of argv) ---
CFG="$(mktemp)"
chmod 600 "$CFG"
trap 'rm -f "$CFG"' EXIT
BASIC="$(printf '%s:%s' "$EMAIL" "$TOKEN" | base64 | tr -d '\n')"
printf 'header = "Authorization: Basic %s"\n' "$BASIC" > "$CFG"

att_run "$INVENTORY" "$CFG" "$DEST" "jira" "$REF"
