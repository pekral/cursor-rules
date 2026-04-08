#!/usr/bin/env bash
# Collect all comments from a GitHub issue timeline.
# Usage: ./collect-issue-comments.sh <ISSUE_NUMBER>

set -euo pipefail

ISSUE="${1:?Usage: collect-issue-comments.sh <ISSUE_NUMBER>}"

gh api "repos/{owner}/{repo}/issues/${ISSUE}/comments" \
  --jq '.[] | {id, user: .user.login, created_at, body}'
