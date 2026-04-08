#!/usr/bin/env bash
# Create a new issue in the tracker, assign to the current user, and apply labels.
# Usage: ./create-issue.sh "<title>" "<body>" "<label1,label2>"

set -euo pipefail

TITLE="${1:?Usage: create-issue.sh \"<title>\" \"<body>\" \"<label1,label2>\"}"
BODY="${2:?Missing issue body}"
LABELS="${3:-from-code-review}"

gh issue create \
  --title "$TITLE" \
  --body "$BODY" \
  --label "$LABELS" \
  --assignee "@me"
