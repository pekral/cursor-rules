#!/usr/bin/env bash
# download-attachments.sh — download attachments referenced by a Bugsnag error into a
# quarantine directory and hand them to the mandatory security scan.
#
# Bugsnag's Data Access API exposes no file-attachment resource — screenshots and logs
# are linked as URLs inside the error comments. This script loads the error via the
# deterministic load-issue.sh (authenticated with BUGSNAG_TOKEN) and extracts those
# comment-linked file URLs.
#
# Usage:
#   download-attachments.sh <URL|ORG_SLUG/PROJECT_SLUG/ERROR_ID> [--dest DIR]
#
# Auth: BUGSNAG_TOKEN authenticates the API read only (load-issue.sh). The linked
# attachment URLs are third-party hosts, so the Bugsnag token is NEVER forwarded to
# them — downloads run unauthenticated with TLS validation on. This prevents leaking
# the org token to an external host.
#
# Exit codes:
#   1  usage error
#   2  missing tool / missing BUGSNAG_TOKEN
#   3  inventory load or download failure
set -euo pipefail

PROG="download-attachments.sh"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SHARED_DIR="${SCRIPT_DIR}/../../_shared"

usage() {
  cat >&2 <<'EOF'
Usage: download-attachments.sh <URL|ORG_SLUG/PROJECT_SLUG/ERROR_ID> [--dest DIR]

  --dest   quarantine root (default: $CLAUDE_SCRATCHPAD_DIR/attachments)

Auth: export BUGSNAG_TOKEN (Data Access API token) to read the error.
EOF
}

REF=""
DEST=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --dest) DEST="${2:-}"; shift 2;;
    -h|--help) usage; exit 1;;
    -*) echo "${PROG}: unknown option: $1" >&2; usage; exit 1;;
    *) if [[ -z "$REF" ]]; then REF="$1"; shift; else echo "${PROG}: unexpected argument: $1" >&2; exit 1; fi;;
  esac
done

if [[ -z "$REF" ]]; then usage; exit 1; fi

# shellcheck source=../../_shared/attachments.sh
. "${SHARED_DIR}/attachments.sh"
att_require_tools

if [[ -z "${BUGSNAG_TOKEN:-${BUGSNAG_AUTH_TOKEN:-}}" ]]; then
  echo "${PROG}: BUGSNAG_TOKEN is not set (export a Data Access API token)." >&2
  exit 2
fi

[[ -z "$DEST" ]] && DEST="$(att_default_dest)/bugsnag"

# stderr suppressed: the loader's own diagnostics are noise; its result is validated on the next line.
ERROR_JSON="$("${SCRIPT_DIR}/load-issue.sh" "$REF" 2>/dev/null || true)"
if [[ -z "$ERROR_JSON" ]] || ! printf '%s' "$ERROR_JSON" | jq -e . >/dev/null 2>&1; then
  echo "${PROG}: failed to load Bugsnag error inventory for: $REF" >&2
  exit 3
fi

# Pull file-looking URLs (with an analysable extension) out of the comment bodies.
ALL_TEXT="$(printf '%s' "$ERROR_JSON" | jq -r '[ (.comments // [])[].body ] | map(select(. != null)) | join("\n")')"
URLS="$(printf '%s' "$ALL_TEXT" \
  | grep -oiE 'https://[^][:space:]")<>]+\.(png|jpe?g|gif|webp|pdf|txt|log|csv|json)' \
  | sort -u || true)"

INVENTORY="$(printf '%s\n' "$URLS" | jq -R -s '
  split("\n") | map(select(length > 0))
  | to_entries | map({
      id: (.key | tostring),
      name: (.value | sub("[?#].*$"; "") | split("/") | last),
      declaredMime: null,
      size: null,
      contentUrl: .value
    })
')"

COUNT="$(printf '%s' "$INVENTORY" | jq 'length')"
if [[ "$COUNT" -eq 0 ]]; then
  echo "${PROG}: no comment-linked attachments found on $REF (nothing to download)." >&2
fi

# No auth config: the Bugsnag token must not be forwarded to third-party hosts.
att_run "$INVENTORY" "" "$DEST" "bugsnag" "$REF"
