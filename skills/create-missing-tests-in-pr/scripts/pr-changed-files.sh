#!/usr/bin/env bash
# List files changed in a PR with their change status.
# Usage: ./pr-changed-files.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: pr-changed-files.sh <PR_NUMBER>}"

gh pr view "$PR" \
  --json files \
  --jq '.files[] | {path: .path, additions: .additions, deletions: .deletions}'
