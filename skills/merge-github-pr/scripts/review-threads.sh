#!/usr/bin/env bash
# Get all inline code review comments (threads) for a PR.
# Usage: ./review-threads.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: review-threads.sh <PR_NUMBER>}"

gh api "repos/{owner}/{repo}/pulls/${PR}/comments" \
  --jq '.[] | {id, path, line, body, user: .user.login, created_at, in_reply_to_id}'
