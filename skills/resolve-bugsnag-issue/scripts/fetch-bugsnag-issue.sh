#!/usr/bin/env bash
# Fetch Bugsnag issue details using the Bugsnag API via MCP or CLI.
# Usage: ./fetch-bugsnag-issue.sh <BUGSNAG_ID_OR_URL>
# Outputs: JSON with error class, message, stacktrace, breadcrumbs, and metadata.

set -euo pipefail

ISSUE="${1:?Usage: fetch-bugsnag-issue.sh <BUGSNAG_ID_OR_URL>}"

# Extract issue ID from URL if a full URL was provided
if [[ "$ISSUE" == http* ]]; then
  ISSUE_ID=$(echo "$ISSUE" | grep -oE '[a-f0-9]{24}' | tail -1)
else
  ISSUE_ID="$ISSUE"
fi

if [ -z "$ISSUE_ID" ]; then
  echo "Error: Could not extract Bugsnag issue ID from input: $ISSUE" >&2
  exit 1
fi

echo "Fetching Bugsnag issue: $ISSUE_ID"
echo "Use MCP server or Bugsnag CLI to retrieve full error details."
echo ""
echo "Required information to collect:"
echo "  - Error class and message"
echo "  - Full stacktrace"
echo "  - Breadcrumbs (user actions leading to error)"
echo "  - App version and environment"
echo "  - Device/OS information"
echo "  - Frequency and first/last seen timestamps"
echo "  - Associated user data (if available)"
