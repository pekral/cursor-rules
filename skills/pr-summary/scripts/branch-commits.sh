#!/usr/bin/env bash
# List all commits in the current branch since it diverged from the base branch.
# Usage: ./branch-commits.sh [base_branch]
# Default base branch: auto-detected (master or main).

set -euo pipefail

BASE="${1:-}"

if [ -z "$BASE" ]; then
  if git rev-parse --verify origin/master >/dev/null 2>&1; then
    BASE="origin/master"
  elif git rev-parse --verify origin/main >/dev/null 2>&1; then
    BASE="origin/main"
  else
    echo "ERROR: Could not detect base branch. Pass it as argument." >&2
    exit 1
  fi
fi

MERGE_BASE=$(git merge-base "$BASE" HEAD)

git log --format="%H %s" "$MERGE_BASE..HEAD"
