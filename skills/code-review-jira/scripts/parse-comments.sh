#!/usr/bin/env bash
# parse-comments.sh — project an issue's comments into a structured JSON array.
#
# Thin projection over load-issue.sh so an AI agent can read and triage comments
# without re-parsing the full issue payload. The heavy lifting (acli call,
# pagination, ADF flattening, KEY/URL normalisation) already lives in
# load-issue.sh — this script only reshapes its `.comments[]` output.
#
# Usage:
#   parse-comments.sh <KEY|URL>
#
# Accepts the same KEY|URL forms as load-issue.sh (bare key, /browse/<KEY> URL,
# or any URL containing ?selectedIssue=<KEY>).
#
# Output (stdout): a JSON array in chronological order, one object per comment:
#   [ { "index", "author", "created", "visibility", "body",
#       "charCount", "lineCount" }, … ]
# An issue with no comments yields []. `charCount`/`lineCount` let the caller
# decide whether to read a comment whole or in chunks.
#
# Exit codes (propagated from load-issue.sh):
#   1  usage / argument error
#   2  missing required tool (jq, or a tool load-issue.sh needs)
#   3  JIRA fetch failed
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: parse-comments.sh <KEY|URL>

  KEY  JIRA issue key (e.g. ECOMAIL-1234)
  URL  /browse/<KEY> URL or any URL containing ?selectedIssue=<KEY>
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

ISSUE_JSON="$("$SCRIPT_DIR/load-issue.sh" "$1")" || exit $?

printf '%s' "$ISSUE_JSON" | jq '
  [ (.comments // [])
    | to_entries[]
    | {
        index:      .key,
        author:     .value.author,
        created:    .value.created,
        visibility: .value.visibility,
        body:       .value.body,
        charCount:  ((.value.body // "") | length),
        lineCount:  ((.value.body // "") | split("\n") | length)
      }
  ]
'
