#!/usr/bin/env bash
# Search for existing open issues that may overlap with a finding.
# Used to prevent duplicate issue creation.
# Usage: ./check-existing-issues.sh "<search query>"

set -euo pipefail

QUERY="${1:?Usage: check-existing-issues.sh \"<search query>\"}"

gh issue list --state open \
  --search "$QUERY" \
  --json number,title,labels,url \
  --jq '
    .[]
    | {
        number,
        title,
        labels: [.labels[].name],
        url
      }
  '
