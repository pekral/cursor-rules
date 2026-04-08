#!/usr/bin/env bash
# Create a GitHub PR for a resolved issue and link it to the issue.
# Usage: ./create-pr.sh <ISSUE_NUMBER> <PR_TITLE> <PR_BODY>

set -euo pipefail

ISSUE="${1:?Usage: create-pr.sh <ISSUE_NUMBER> <PR_TITLE> <PR_BODY>}"
TITLE="${2:?Missing PR title}"
BODY="${3:?Missing PR body}"

BRANCH=$(git rev-parse --abbrev-ref HEAD)

echo "=== Pushing branch: $BRANCH ==="
git push -u origin "$BRANCH"

echo "=== Creating PR ==="
gh pr create \
  --title "$TITLE" \
  --body "$BODY" \
  --head "$BRANCH"

echo "=== Linking PR to issue #$ISSUE ==="
PR_URL=$(gh pr view "$BRANCH" --json url --jq '.url')
gh issue comment "$ISSUE" --body "Resolved in $PR_URL"

echo "PR created: $PR_URL"
