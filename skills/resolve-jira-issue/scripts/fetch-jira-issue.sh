#!/usr/bin/env bash
# Fetch full JIRA issue details including comments and attachments.
# Usage: ./fetch-jira-issue.sh <ISSUE_KEY>
# Requires: jira CLI (go-jira or atlassian-cli) configured with credentials.

set -euo pipefail

ISSUE="${1:?Usage: fetch-jira-issue.sh <ISSUE_KEY>}"

# Fetch issue details with all fields, comments, and attachment metadata
jira issue view "$ISSUE" --comments 999
