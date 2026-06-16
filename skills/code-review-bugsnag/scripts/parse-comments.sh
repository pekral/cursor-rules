#!/usr/bin/env bash
# parse-comments.sh — project a Bugsnag error's comments into a structured
# JSON array.
#
# Thin projection over load-issue.sh so an AI agent can read and triage comments
# without re-parsing the full error payload. The heavy lifting (Bugsnag Data
# Access API calls, slug resolution, JSON shaping) already lives in
# load-issue.sh — this script only reshapes its `.comments[]` output.
#
# Usage:
#   parse-comments.sh <URL|ORG/PROJECT/ERROR_ID>
#
# Accepts the same forms as load-issue.sh (dashboard URL or org/project/error
# slash triple) and requires BUGSNAG_TOKEN the same way.
#
# Output (stdout): a JSON array in chronological order, one object per comment:
#   [ { "index", "author", "email", "created", "updated", "body",
#       "charCount", "lineCount" }, … ]
# An error with no comments yields []. `charCount`/`lineCount` let the caller
# decide whether to read a comment whole or in chunks.
#
# Exit codes (propagated from load-issue.sh):
#   1  usage / argument error
#   2  missing required tool (jq) or missing BUGSNAG_TOKEN
#   3  Bugsnag fetch failed
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: parse-comments.sh <URL|ORG_SLUG/PROJECT_SLUG/ERROR_ID>

  URL     a Bugsnag dashboard error URL
  TRIPLE  an org-slug/project-slug/error-id triple

Requires BUGSNAG_TOKEN (Data Access API token).
EOF
}

if [[ $# -ne 1 || -z "${1:-}" ]]; then
  usage
  exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
  echo "parse-comments.sh: required tool not found: jq" >&2
  exit 2
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

ERROR_JSON="$("$SCRIPT_DIR/load-issue.sh" "$1")" || exit $?

printf '%s' "$ERROR_JSON" | jq '
  [ (.comments // [])
    | to_entries[]
    | {
        index:     .key,
        author:    .value.author,
        email:     .value.email,
        created:   .value.createdAt,
        updated:   .value.updatedAt,
        body:      .value.body,
        charCount: ((.value.body // "") | length),
        lineCount: ((.value.body // "") | split("\n") | length)
      }
  ]
'
