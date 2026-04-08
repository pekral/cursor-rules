#!/usr/bin/env bash
# Transition a JIRA issue to a target status.
# Usage: ./transition-jira-issue.sh <ISSUE_KEY> <STATUS>
# Example: ./transition-jira-issue.sh PROJ-1234 "Ready for Review"
# Requires: jira CLI configured with credentials.

set -euo pipefail

ISSUE="${1:?Usage: transition-jira-issue.sh <ISSUE_KEY> <STATUS>}"
STATUS="${2:?Usage: transition-jira-issue.sh <ISSUE_KEY> <STATUS>}"

jira issue move "$ISSUE" "$STATUS"
