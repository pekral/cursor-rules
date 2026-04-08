#!/usr/bin/env bash
# Create a single GitHub issue and assign it to the current user.
# Usage: ./create-issue.sh "<title>" "<body>" [label...]
# Example: ./create-issue.sh "[Step 1/3] Create migration" "## Goal\n..." "enhancement"

set -euo pipefail

TITLE="${1:?Title is required}"
BODY="${2:?Body is required}"
shift 2

LABEL_ARGS=()
for label in "$@"; do
  LABEL_ARGS+=(--label "$label")
done

CURRENT_USER=$(gh api user --jq '.login')

gh issue create \
  --title "$TITLE" \
  --body "$BODY" \
  --assignee "$CURRENT_USER" \
  "${LABEL_ARGS[@]}"
