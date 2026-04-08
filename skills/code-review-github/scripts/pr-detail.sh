#!/usr/bin/env bash
# Get full PR detail: body, reviews, comments, CI checks, merge status.
# Usage: ./pr-detail.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: pr-detail.sh <PR_NUMBER>}"

gh pr view "$PR" \
  --json number,title,state,mergeable,mergeStateStatus,reviewDecision,headRefName,baseRefName,body,reviews,comments,statusCheckRollup
