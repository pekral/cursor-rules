#!/usr/bin/env bash
# Fetch full PR context: body, diff stats, commits, and linked issues.
# Usage: ./fetch-pr-context.sh <PR_URL_OR_NUMBER>

set -euo pipefail

PR="${1:?Usage: fetch-pr-context.sh <PR_URL_OR_NUMBER>}"

echo "=== PR Detail ==="
gh pr view "$PR" \
  --json number,title,state,body,commits,files,labels,assignees

echo ""
echo "=== PR Diff (stat) ==="
gh pr diff "$PR" --stat
