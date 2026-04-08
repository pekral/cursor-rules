#!/usr/bin/env bash
# Load full PR detail: body, reviews, comments, CI checks.
# Usage: ./load-pr.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: load-pr.sh <PR_NUMBER>}"

gh pr view "$PR" \
  --json number,title,state,body,reviews,comments,statusCheckRollup,headRefName,baseRefName
