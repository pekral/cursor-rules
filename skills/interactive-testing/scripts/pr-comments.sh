#!/usr/bin/env bash
# Load all PR comments and review threads to find testing instructions.
# Usage: ./pr-comments.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: pr-comments.sh <PR_NUMBER>}"

echo "=== PR Comments ==="
gh pr view "$PR" --json comments --jq '.comments[].body'

echo ""
echo "=== Review Comments ==="
gh api "repos/{owner}/{repo}/pulls/${PR}/comments" --jq '.[].body'

echo ""
echo "=== Review Bodies ==="
gh pr view "$PR" --json reviews --jq '.reviews[].body | select(. != "")'
