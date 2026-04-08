#!/usr/bin/env bash
# Check if a PR has merge conflicts with its base branch.
# Usage: ./check-pr-conflicts.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: check-pr-conflicts.sh <PR_NUMBER>}"

gh pr view "$PR" --json mergeable,mergeStateStatus --jq '"\(.mergeable) \(.mergeStateStatus)"'
