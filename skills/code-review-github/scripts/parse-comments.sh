#!/usr/bin/env bash
# parse-comments.sh — project a GitHub issue/PR's comments into a structured
# JSON array.
#
# Thin projection over load-issue.sh so an AI agent can read and triage comments
# without re-parsing the full issue/PR payload. The heavy lifting (gh call,
# NUMBER/URL normalisation, JSON shaping) already lives in load-issue.sh — this
# script only reshapes its `.comments[]` output.
#
# Usage:
#   parse-comments.sh <NUMBER|URL>
#
# Accepts the same NUMBER|URL forms as load-issue.sh (bare number, /issues/<N>
# URL, or /pull/<N> URL).
#
# Output (stdout): a JSON array in chronological order, one object per comment:
#   [ { "index", "author", "created", "updated", "url", "body",
#       "charCount", "lineCount" }, … ]
# An issue/PR with no comments yields []. `charCount`/`lineCount` let the caller
# decide whether to read a comment whole or in chunks.
#
# Exit codes (propagated from load-issue.sh):
#   1  usage / argument error
#   2  missing required tool (jq, or a tool load-issue.sh needs)
#   3  GitHub fetch failed
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: parse-comments.sh <NUMBER|URL>

  NUMBER  bare GitHub issue or PR number (e.g. 445)
  URL     any github.com URL containing /issues/<N> or /pull/<N>
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
        index:     .key,
        author:    .value.author,
        created:   .value.createdAt,
        updated:   .value.updatedAt,
        url:       .value.url,
        body:      .value.body,
        charCount: ((.value.body // "") | length),
        lineCount: ((.value.body // "") | split("\n") | length)
      }
  ]
'
