#!/usr/bin/env bash
# Fetch all review comments (inline code review threads) for a PR.
# Usage: ./pr-review-comments.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: pr-review-comments.sh <PR_NUMBER>}"

gh pr view "$PR" \
  --json reviews,comments \
  --jq '{
    reviews: [.reviews[] | {author: .author.login, state: .state, body: .body}],
    comments: [.comments[] | {author: .author.login, body: .body}]
  }'
