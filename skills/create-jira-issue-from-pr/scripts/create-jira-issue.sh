#!/usr/bin/env bash
# Create a JIRA issue and assign it to the current user.
# Usage: ./create-jira-issue.sh <PROJECT_KEY> <SUMMARY> <BODY_FILE>
# BODY_FILE should contain the issue description in JIRA markdown format.

set -euo pipefail

PROJECT="${1:?Usage: create-jira-issue.sh <PROJECT_KEY> <SUMMARY> <BODY_FILE>}"
SUMMARY="${2:?Missing SUMMARY argument}"
BODY_FILE="${3:?Missing BODY_FILE argument}"

if [ ! -f "$BODY_FILE" ]; then
  echo "Error: Body file '$BODY_FILE' not found." >&2
  exit 1
fi

BODY=$(cat "$BODY_FILE")

jira issue create \
  --project "$PROJECT" \
  --type Task \
  --summary "$SUMMARY" \
  --body "$BODY" \
  --assignee "$(jira me)" \
  --no-input
