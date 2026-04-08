#!/usr/bin/env bash
# Get issue detail including body and comments for plan alignment analysis.
# Usage: ./issue-detail.sh <ISSUE_NUMBER>

set -euo pipefail

ISSUE="${1:?Usage: issue-detail.sh <ISSUE_NUMBER>}"

gh issue view "$ISSUE" --json number,title,body,comments,labels,assignees
