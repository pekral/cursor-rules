#!/usr/bin/env bash
# Merge a PR using rebase strategy (preferred), with delete-branch.
# Usage: ./merge-pr.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: merge-pr.sh <PR_NUMBER>}"

gh pr merge "$PR" --rebase --delete-branch
