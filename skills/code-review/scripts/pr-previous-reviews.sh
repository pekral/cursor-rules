#!/usr/bin/env bash
# Usage: scripts/pr-previous-reviews.sh <PR_NUMBER>
# Fetches all review comments and issue comments for dedup purposes.
# Outputs review bodies and comment bodies so the agent can build a dedup list.

set -euo pipefail

PR="${1:?Usage: pr-previous-reviews.sh <PR_NUMBER>}"

echo "=== Review submissions ==="
gh pr view "$PR" --json reviews --jq '.reviews[] | "[\(.state)] \(.author.login): \(.body)"'

echo ""
echo "=== PR comments ==="
gh pr view "$PR" --comments --json comments --jq '.comments[] | "\(.author.login): \(.body)"'

echo ""
echo "=== Issue comments ==="
ISSUE=$(gh pr view "$PR" --json body --jq '.body' | grep -oP '#\d+' | head -1 | tr -d '#' || true)
if [ -n "$ISSUE" ]; then
    gh issue view "$ISSUE" --comments --json comments --jq '.comments[] | "\(.author.login): \(.body)"' 2>/dev/null || echo "(no linked issue or comments)"
else
    echo "(no linked issue found)"
fi
