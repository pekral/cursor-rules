#!/usr/bin/env bash
# Post a comment on an existing GitHub issue.
# Usage: ./comment-on-issue.sh <issue-number> "<comment-body>"

set -euo pipefail

ISSUE="${1:?Issue number is required}"
BODY="${2:?Comment body is required}"

gh issue comment "$ISSUE" --body "$BODY"
