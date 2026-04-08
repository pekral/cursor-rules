#!/usr/bin/env bash
# Find all open pull requests linked to a task/issue.
# Usage: ./find-prs-for-task.sh <ISSUE_ID_OR_URL>

set -euo pipefail

ISSUE="${1:?Usage: find-prs-for-task.sh <ISSUE_ID_OR_URL>}"

gh pr list --state open --search "$ISSUE" --json number,title,headRefName,baseRefName,mergeable,reviewDecision,statusCheckRollup
