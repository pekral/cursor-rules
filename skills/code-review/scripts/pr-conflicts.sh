#!/usr/bin/env bash
# Usage: scripts/pr-conflicts.sh <PR_NUMBER>
# Checks if a PR has merge conflicts with its base branch.
# Exits 0 if clean, exits 1 if conflicts are detected.

set -euo pipefail

PR="${1:?Usage: pr-conflicts.sh <PR_NUMBER>}"

MERGEABLE=$(gh pr view "$PR" --json mergeable --jq '.mergeable')

if [ "$MERGEABLE" = "CONFLICTING" ]; then
    echo "CONFLICT: PR #${PR} has merge conflicts with the base branch."
    exit 1
elif [ "$MERGEABLE" = "MERGEABLE" ]; then
    echo "OK: PR #${PR} has no conflicts."
    exit 0
else
    echo "UNKNOWN: PR #${PR} mergeability status is '${MERGEABLE}'."
    exit 2
fi
