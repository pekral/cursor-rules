#!/usr/bin/env bash
# Post a review comment (general comment) to a PR.
# Usage: ./post-review-comment.sh <PR_NUMBER> <BODY_FILE>
# BODY_FILE should contain the Markdown content to post.

set -euo pipefail

PR="${1:?Usage: post-review-comment.sh <PR_NUMBER> <BODY_FILE>}"
BODY_FILE="${2:?Usage: post-review-comment.sh <PR_NUMBER> <BODY_FILE>}"

if [ ! -f "$BODY_FILE" ]; then
  echo "Error: Body file '$BODY_FILE' not found." >&2
  exit 1
fi

gh pr comment "$PR" --body-file "$BODY_FILE"
