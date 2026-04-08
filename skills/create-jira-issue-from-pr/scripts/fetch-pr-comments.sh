#!/usr/bin/env bash
# Fetch all review comments and review threads for a PR.
# Usage: ./fetch-pr-comments.sh <PR_URL_OR_NUMBER>

set -euo pipefail

PR="${1:?Usage: fetch-pr-comments.sh <PR_URL_OR_NUMBER>}"

echo "=== Reviews ==="
gh pr view "$PR" --json reviews --jq '.reviews[] | {author: .author.login, state: .state, body: .body}'

echo ""
echo "=== Comments ==="
gh pr view "$PR" --json comments --jq '.comments[] | {author: .author.login, body: .body, createdAt: .createdAt}'

echo ""
echo "=== Review Threads ==="
gh api "repos/{owner}/{repo}/pulls/${PR}/comments" --jq '.[] | {path: .path, line: .line, body: .body, user: .user.login, in_reply_to_id: .in_reply_to_id}'
