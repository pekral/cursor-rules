#!/usr/bin/env bash
# List unresolved JIRA issues labeled "Resolve_by_AI" that are eligible for AI resolution.
# Requires: jira CLI (https://github.com/ankitpokhrel/jira-cli) configured with project.
# Usage: ./list-jira-candidates.sh [PROJECT_KEY]

set -euo pipefail

PROJECT="${1:-}"

if [ -z "$PROJECT" ]; then
  echo "Usage: $0 <PROJECT_KEY>" >&2
  exit 1
fi

jira issue list \
  --project "$PROJECT" \
  --label "Resolve_by_AI" \
  --status "~Done" \
  --status "~Closed" \
  --plain \
  --columns key,summary,type,status,priority
