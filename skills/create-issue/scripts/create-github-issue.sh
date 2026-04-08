#!/usr/bin/env bash
# Create a GitHub issue and assign it to the current user.
# Usage: ./create-github-issue.sh <TITLE> <BODY_FILE>
# BODY_FILE should be a path to a file containing the issue body in markdown.

set -euo pipefail

TITLE="${1:?Usage: create-github-issue.sh <TITLE> <BODY_FILE>}"
BODY_FILE="${2:?Usage: create-github-issue.sh <TITLE> <BODY_FILE>}"

if [ ! -f "$BODY_FILE" ]; then
  echo "ERROR: Body file not found: $BODY_FILE" >&2
  exit 1
fi

gh issue create \
  --title "$TITLE" \
  --body-file "$BODY_FILE" \
  --assignee @me
