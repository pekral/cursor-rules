#!/usr/bin/env bash
# Show the full diff of the current branch against the base branch.
# Usage: ./branch-diff.sh [base_branch]
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

git diff "$BASE"...HEAD
