#!/usr/bin/env bash
# download-attachments.sh — download every attachment of a GitHub issue / PR (and its
# sub-issues / comments) into a quarantine directory and hand them to the mandatory
# security scan.
#
# GitHub has no structured attachment field — uploaded files live as inline URLs in
# the issue / comment Markdown. This script extracts those URLs from the body and all
# comments (via the deterministic load-issue.sh) and downloads each one.
#
# Usage:
#   download-attachments.sh <NUMBER|URL> [--dest DIR]
#
# Auth: a Bearer token from `gh auth token`, written only into a 0600 curl --config
# file (never in argv / logs). The token is sent to github.com only — curl follows the
# redirect to signed storage with `-L` (not --location-trusted), so the credential is
# not forwarded to the storage host. TLS validation stays on throughout.
#
# Exit codes:
#   1  usage error
#   2  missing tool / no gh auth token
#   3  inventory load or download failure
set -euo pipefail

PROG="download-attachments.sh"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SHARED_DIR="${SCRIPT_DIR}/../../_shared"

usage() {
  cat >&2 <<'EOF'
Usage: download-attachments.sh <NUMBER|URL> [--dest DIR]

  NUMBER|URL   GitHub issue / PR number or URL
  --dest       quarantine root (default: $CLAUDE_SCRATCHPAD_DIR/attachments)

Auth: uses `gh auth token` (run `gh auth login` first).
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
if ! command -v gh >/dev/null 2>&1; then
  echo "${PROG}: required tool not found: gh" >&2
  exit 2
fi

[[ -z "$DEST" ]] && DEST="$(att_default_dest)/github"

# stderr suppressed: gh's own diagnostics are noise; the empty-token case is handled on the next line.
TOKEN="$(gh auth token 2>/dev/null || true)"
if [[ -z "$TOKEN" ]]; then
  echo "${PROG}: no GitHub token from 'gh auth token'. Run 'gh auth login' first." >&2
  exit 2
fi

# --- load body + comments via the deterministic loader and extract attachment URLs ---
# stderr suppressed: the loader's own diagnostics are noise; its result is validated on the next line.
ISSUE_JSON="$("${SCRIPT_DIR}/load-issue.sh" "$REF" 2>/dev/null || true)"
if [[ -z "$ISSUE_JSON" ]] || ! printf '%s' "$ISSUE_JSON" | jq -e . >/dev/null 2>&1; then
  echo "${PROG}: failed to load GitHub issue inventory for: $REF" >&2
  exit 3
fi

# Collect every Markdown text surface (body + comments + sub-issue bodies/comments),
# then pull GitHub-hosted upload URLs out of them. Only github.com / githubusercontent
# hosts are followed, per the outbound-allowlist rule in rules/security/backend.md.
ALL_TEXT="$(printf '%s' "$ISSUE_JSON" | jq -r '
  ( [ .body ]
    + [ (.comments // [])[].body ]
    + [ (.subIssues // [])[].body ]
    + [ (.subIssues // [])[] | (.comments // [])[].body ]
  ) | map(select(. != null)) | join("\n")
')"

URLS="$(printf '%s' "$ALL_TEXT" \
  | grep -oE 'https://(github\.com/user-attachments/(assets|files)/[^][:space:]")<>]+|github\.com/[^/]+/[^/]+/(assets|files)/[^][:space:]")<>]+|[a-z-]*\.?githubusercontent\.com/[^][:space:]")<>]+)' \
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
  echo "${PROG}: no inline attachments found on $REF (nothing to download)." >&2
fi

# --- write the 0600 curl auth config (token stays out of argv) ---
CFG="$(mktemp)"
chmod 600 "$CFG"
trap 'rm -f "$CFG"' EXIT
printf 'header = "Authorization: Bearer %s"\n' "$TOKEN" > "$CFG"

att_run "$INVENTORY" "$CFG" "$DEST" "github" "$REF"
