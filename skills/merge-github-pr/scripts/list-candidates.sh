#!/usr/bin/env bash
# List open PRs with merge-readiness summary (CI status, review decision, conflicts).
# Usage: ./list-candidates.sh

set -euo pipefail

gh pr list --state open \
  --json number,title,mergeable,mergeStateStatus,reviewDecision,headRefName,baseRefName,statusCheckRollup \
  --jq '
    .[] | {
      number,
      title,
      mergeable,
      mergeStateStatus,
      reviewDecision,
      branch: .headRefName,
      base: .baseRefName,
      ci_all_passed: (
        if (.statusCheckRollup | length) == 0 then "NO_CHECKS"
        elif all(.statusCheckRollup[]; .conclusion == "SUCCESS") then "YES"
        else "NO"
        end
      ),
      checks: [.statusCheckRollup[] | {name, status, conclusion}]
    }
  '
