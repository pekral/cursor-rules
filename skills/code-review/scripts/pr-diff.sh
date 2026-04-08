#!/usr/bin/env bash
# Usage: scripts/pr-diff.sh [<PR_NUMBER>]
# Shows the diff of a PR against its base branch.
# If no PR number is given, shows diff of current branch vs main.

set -euo pipefail

if [ -n "${1:-}" ]; then
    gh pr diff "$1"
else
    BASE=$(git symbolic-ref refs/remotes/origin/HEAD 2>/dev/null | sed 's@refs/remotes/origin/@@' || echo "master")
    git diff "${BASE}...HEAD"
fi
