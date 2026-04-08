#!/usr/bin/env bash
# Fetch all review comments and threads for a PR.
# Usage: ./pr-review-comments.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: pr-review-comments.sh <PR_NUMBER>}"

echo "=== Reviews ==="
gh pr view "$PR" --json reviews --jq '.reviews[] | "[\(.state)] \(.author.login): \(.body)"'

echo ""
echo "=== Review threads (inline comments) ==="
gh api "repos/{owner}/{repo}/pulls/$PR/comments" --jq '.[] | "[\(.path):\(.line // .original_line)] \(.user.login): \(.body)"'

echo ""
echo "=== General comments ==="
gh pr view "$PR" --json comments --jq '.comments[] | "\(.author.login): \(.body)"'
