#!/usr/bin/env bash
# Fetch full GitHub issue details: body, comments, labels, assignees, linked PRs.
# Usage: ./fetch-issue.sh <ISSUE_NUMBER_OR_URL>

set -euo pipefail

ISSUE="${1:?Usage: fetch-issue.sh <ISSUE_NUMBER_OR_URL>}"

gh issue view "$ISSUE" \
  --json number,title,state,body,comments,labels,assignees,milestone
