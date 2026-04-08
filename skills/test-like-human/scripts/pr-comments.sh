#!/usr/bin/env bash
# Load PR conversation: description, review comments, and discussion threads.
# Usage: ./pr-comments.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: pr-comments.sh <PR_NUMBER>}"

echo "=== PR Description ==="
gh pr view "$PR" --json body --jq '.body'

echo ""
echo "=== Review Comments ==="
gh pr view "$PR" --json reviews --jq '.reviews[] | "[\(.state)] \(.author.login): \(.body)"'

echo ""
echo "=== Discussion Comments ==="
gh pr view "$PR" --json comments --jq '.comments[] | "\(.author.login): \(.body)"'
