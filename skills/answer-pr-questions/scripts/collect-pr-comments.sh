#!/usr/bin/env bash
# Collect all timeline comments from a GitHub pull request.
# Usage: ./collect-pr-comments.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: collect-pr-comments.sh <PR_NUMBER>}"

gh api "repos/{owner}/{repo}/issues/${PR}/comments" \
  --jq '.[] | {id, user: .user.login, created_at, body}'
