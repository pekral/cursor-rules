#!/usr/bin/env bash
# Usage: scripts/list-commits.sh [<PR_NUMBER>]
# Lists commits in a PR, or commits on current branch vs main.

set -euo pipefail

if [ -n "${1:-}" ]; then
    gh pr view "$1" --json commits --jq '.commits[] | "\(.oid[0:8]) \(.messageHeadline)"'
else
    BASE=$(git symbolic-ref refs/remotes/origin/HEAD 2>/dev/null | sed 's@refs/remotes/origin/@@' || echo "master")
    git log --oneline "${BASE}..HEAD"
fi
