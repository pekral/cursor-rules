#!/usr/bin/env bash
# Get all comments and review comments from a PR for deduplication.
# Usage: ./pr-comments.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: pr-comments.sh <PR_NUMBER>}"

echo "=== PR comments ==="
gh pr view "$PR" --json comments --jq '.comments[] | {author: .author.login, body: .body, createdAt: .createdAt}'

echo ""
echo "=== Review comments (inline threads) ==="
gh api "repos/{owner}/{repo}/pulls/${PR}/comments" --jq '.[] | {path: .path, line: .line, author: .user.login, body: .body, createdAt: .created_at}'
