#!/usr/bin/env bash
# Load PR description and linked issues for the current branch.
# Usage: ./pr-context.sh
# Requires: gh CLI authenticated.

set -euo pipefail

BRANCH=$(git branch --show-current)

PR_JSON=$(gh pr list --head "$BRANCH" --state open --json number,title,body,labels,url --jq '.[0] // empty')

if [ -z "$PR_JSON" ]; then
  echo "No open PR found for branch: $BRANCH" >&2
  exit 0
fi

echo "$PR_JSON"
