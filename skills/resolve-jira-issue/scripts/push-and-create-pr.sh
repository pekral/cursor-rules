#!/usr/bin/env bash
# Push the current branch and create a GitHub PR linked to a JIRA issue.
# Usage: ./push-and-create-pr.sh <ISSUE_KEY> [BASE_BRANCH]
# Requires: gh CLI authenticated, git configured.

set -euo pipefail

ISSUE="${1:?Usage: push-and-create-pr.sh <ISSUE_KEY> [BASE_BRANCH]}"
BASE="${2:-main}"
BRANCH=$(git rev-parse --abbrev-ref HEAD)

# Push the branch
git push -u origin "$BRANCH"

# Create PR with JIRA issue reference in the body
gh pr create \
  --base "$BASE" \
  --head "$BRANCH" \
  --title "$(git log -1 --format=%s)" \
  --body "$(cat <<EOF
## JIRA Issue

[$ISSUE]

## Summary

<!-- Describe the changes -->

## Test plan

- [ ] All tests pass
- [ ] 100% coverage on changed files
- [ ] Code review cycle clean (no Critical/Moderate findings)
EOF
)"
