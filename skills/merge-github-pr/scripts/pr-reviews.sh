#!/usr/bin/env bash
# Get all review submissions (approvals, change requests, comments) for a PR.
# Usage: ./pr-reviews.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: pr-reviews.sh <PR_NUMBER>}"

gh api "repos/{owner}/{repo}/pulls/${PR}/reviews" \
  --jq '.[] | {id, state, body, user: .user.login, submitted_at}'
