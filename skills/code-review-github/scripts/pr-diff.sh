#!/usr/bin/env bash
# Get the full diff for a PR.
# Usage: ./pr-diff.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: pr-diff.sh <PR_NUMBER>}"

gh pr diff "$PR"
