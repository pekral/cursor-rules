#!/usr/bin/env bash
# Collect all review comments (inline code review threads) from a PR.
# Usage: ./collect-review-threads.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: collect-review-threads.sh <PR_NUMBER>}"

gh api "repos/{owner}/{repo}/pulls/${PR}/comments" \
  --jq '.[] | {id, user: .user.login, created_at, path, body, in_reply_to_id}'
