#!/usr/bin/env bash
# Post a comment to a GitHub PR.
# Usage: ./post-pr-comment.sh <PR_NUMBER> <BODY>
# Example: ./post-pr-comment.sh 142 "No findings were identified."

set -euo pipefail

PR="${1:?Usage: post-pr-comment.sh <PR_NUMBER> <BODY>}"
BODY="${2:?Usage: post-pr-comment.sh <PR_NUMBER> <BODY>}"

gh pr comment "$PR" --body "$BODY"
