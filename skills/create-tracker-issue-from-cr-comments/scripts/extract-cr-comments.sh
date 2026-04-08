#!/usr/bin/env bash
# Extract code review findings from a PR that may require issue creation.
# Collects review bodies and inline comments, excluding resolved threads.
# Usage: ./extract-cr-comments.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: extract-cr-comments.sh <PR_NUMBER>}"

echo "=== Review submissions ==="
gh pr view "$PR" --json reviews \
  --jq '
    .reviews[]
    | select(.state == "CHANGES_REQUESTED" or .state == "COMMENTED")
    | select(.body != null and .body != "")
    | {
        reviewer: .author.login,
        state: .state,
        body: .body,
        submittedAt: .submittedAt
      }
  '

echo ""
echo "=== Inline review comments ==="
gh api "repos/{owner}/{repo}/pulls/${PR}/comments" \
  --paginate \
  --jq '
    .[]
    | {
        id: .id,
        reviewer: .user.login,
        file: .path,
        line: .line,
        body: .body,
        in_reply_to: .in_reply_to_id,
        created_at: .created_at,
        url: .html_url
      }
  '
