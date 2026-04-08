#!/usr/bin/env bash
# Find open PRs linked to a JIRA issue by branch name pattern.
# Usage: ./find-linked-prs.sh <JIRA_KEY>
# Example: ./find-linked-prs.sh PROJ-1234

set -euo pipefail

JIRA_KEY="${1:?Usage: find-linked-prs.sh <JIRA_KEY>}"

gh pr list --state open \
  --json number,title,headRefName,baseRefName,mergeable,mergeStateStatus \
  --jq "
    .[] | select(.headRefName | test(\"(?i)${JIRA_KEY}\")) | {
      number,
      title,
      branch: .headRefName,
      base: .baseRefName,
      mergeable,
      mergeStateStatus
    }
  "
