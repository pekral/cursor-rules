#!/usr/bin/env bash
# transition-to-code-review.sh — move a JIRA issue to the project's Code Review
# status, and nothing else.
#
# Status transitions are otherwise human-only (rules/jira/general.mdc). This
# script is the single sanctioned exception: it can ONLY land an issue in a
# review status. It structurally refuses any other target (Done, In Progress,
# Closed, …) so an AI agent cannot use it to push work through the board.
#
# Usage:
#   transition-to-code-review.sh <KEY|URL> [<STATUS>]
#
# Inputs:
#   KEY|URL  Bare JIRA key (e.g. ECOMAIL-1234), a /browse/<KEY> URL, or any URL
#            containing ?selectedIssue=<KEY>.
#   STATUS   Optional exact target status name. Resolution order:
#              1. this argument
#              2. $JIRA_CODE_REVIEW_STATUS
#              3. default "Code Review"
#            Every project may name its review column differently
#            (e.g. "Code Review", "In Review", "Review / feedback"), so the
#            target is validated by the review-name guard below rather than
#            hardcoded.
#
# Review-name guard:
#   The target is accepted only when it case-insensitively contains "review" OR
#   is listed in $JIRA_CODE_REVIEW_SYNONYMS (comma-separated). Anything else is
#   refused with exit 1 and the issue is left untouched.
#
# Behavior:
#   1. Normalise the KEY.
#   2. Validate the requested target against the review-name guard.
#   3. Read the current status via load-issue.sh. If already in the target
#      status, no-op (idempotent) and exit 0.
#   4. Run `acli jira workitem transition --key <KEY> --status <target> --yes`.
#
# acli cannot list a project's available transitions (see load-issue.sh "Known
# limitations"). When the transition fails because the target status does not
# exist in this project / is not reachable from the current status, the script
# exits 5 so the caller can discover the real review-status name via the JIRA
# MCP server (available next transitions), re-validate it against the guard, and
# re-run with the correct STATUS — or ask a human when it cannot be determined.
#
# Output:
#   The issue URL on stdout. `action=transitioned|noop` plus the resolved status
#   on stderr.
#
# Exit codes:
#   1  usage / argument error, or refused target (not a review status)
#   2  missing required tool (acli, jq)
#   3  JIRA API call failed (read or transition, for reasons other than 5)
#   5  target status not available in this project — discover via MCP / ask
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: transition-to-code-review.sh <KEY|URL> [<STATUS>]

  KEY     JIRA issue key (e.g. ECOMAIL-1234)
  URL     /browse/<KEY> URL or any URL containing ?selectedIssue=<KEY>
  STATUS  optional exact target status name (default: $JIRA_CODE_REVIEW_STATUS
          or "Code Review"); must be a review status per the review-name guard
EOF
}

if [[ $# -lt 1 || $# -gt 2 || -z "${1:-}" ]]; then
  usage
  exit 1
fi

for bin in acli jq; do
  if ! command -v "$bin" >/dev/null 2>&1; then
    echo "transition-to-code-review.sh: required tool not found: $bin" >&2
    exit 2
  fi
done

INPUT="$1"
TARGET="${2:-${JIRA_CODE_REVIEW_STATUS:-Code Review}}"

KEY=""
if [[ "$INPUT" =~ ^[A-Z][A-Z0-9_]+-[0-9]+$ ]]; then
  KEY="$INPUT"
elif [[ "$INPUT" == *"/browse/"* ]]; then
  KEY="$(printf '%s' "$INPUT" | sed -nE 's#.*/browse/([A-Z][A-Z0-9_]+-[0-9]+).*#\1#p')"
elif [[ "$INPUT" == *"selectedIssue="* ]]; then
  KEY="$(printf '%s' "$INPUT" | sed -nE 's#.*selectedIssue=([A-Z][A-Z0-9_]+-[0-9]+).*#\1#p')"
fi

if [[ -z "$KEY" ]]; then
  echo "transition-to-code-review.sh: could not extract JIRA key from input: $INPUT" >&2
  exit 1
fi

# Review-name guard: accept only review-ish targets. Substring "review" covers
# the common cross-project names; the env list is the escape hatch for a project
# whose review column has no "review" in its name.
target_lower="$(printf '%s' "$TARGET" | tr '[:upper:]' '[:lower:]')"
is_review=false
if [[ "$target_lower" == *review* ]]; then
  is_review=true
elif [[ -n "${JIRA_CODE_REVIEW_SYNONYMS:-}" ]]; then
  IFS=',' read -ra SYNONYMS <<<"$JIRA_CODE_REVIEW_SYNONYMS"
  for syn in "${SYNONYMS[@]}"; do
    syn_trimmed="$(printf '%s' "$syn" | sed -E 's#^[[:space:]]+|[[:space:]]+$##g' | tr '[:upper:]' '[:lower:]')"
    if [[ -n "$syn_trimmed" && "$target_lower" == "$syn_trimmed" ]]; then
      is_review=true
      break
    fi
  done
fi

if [[ "$is_review" != true ]]; then
  echo "transition-to-code-review.sh: refused — '$TARGET' is not a Code Review status. This script only transitions to a review status; every other transition is human-only (rules/jira/general.mdc)." >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Read current status for the idempotence check.
if ! ISSUE_JSON="$("$SCRIPT_DIR/load-issue.sh" "$KEY")"; then
  echo "transition-to-code-review.sh: failed to read current status of $KEY" >&2
  exit 3
fi
CURRENT_STATUS="$(printf '%s' "$ISSUE_JSON" | jq -r '.status // empty')"
SITE="$(printf '%s' "$ISSUE_JSON" | jq -r '.url // empty' | sed -nE 's#https?://([^/]+)/.*#\1#p')"

if [[ -n "$CURRENT_STATUS" && "$(printf '%s' "$CURRENT_STATUS" | tr '[:upper:]' '[:lower:]')" == "$target_lower" ]]; then
  echo "https://${SITE:-}/browse/${KEY}"
  echo "action=noop status=${CURRENT_STATUS} (already in review)" >&2
  exit 0
fi

# acli transitions by target status name. Capture stderr so a "status not
# available / not found" failure can be distinguished and surfaced as exit 5.
TRANSITION_ERR="$(acli jira workitem transition --key "$KEY" --status "$TARGET" --yes 2>&1 >/dev/null)" && TRANSITION_OK=true || TRANSITION_OK=false

# acli can return success for a "looped transition" that performs an action but
# keeps the current status, or match a transition that does not actually land in
# the requested target. Re-read the status and only report success when the
# issue genuinely reached the target — otherwise treat it as not-reachable (5)
# so the caller discovers the real review-status name instead of trusting a
# false positive.
if [[ "$TRANSITION_OK" == true ]]; then
  NEW_STATUS="$("$SCRIPT_DIR/load-issue.sh" "$KEY" 2>/dev/null | jq -r '.status // empty')"
  if [[ "$(printf '%s' "$NEW_STATUS" | tr '[:upper:]' '[:lower:]')" == "$target_lower" ]]; then
    echo "https://${SITE:-}/browse/${KEY}"
    echo "action=transitioned from=${CURRENT_STATUS:-?} to=${NEW_STATUS}" >&2
    exit 0
  fi
  echo "transition-to-code-review.sh: acli reported success but $KEY is still '${NEW_STATUS:-?}', not '$TARGET' (likely a looped transition or a name mismatch)." >&2
  echo "transition-to-code-review.sh: discover the real review status via JIRA MCP (available next transitions), then re-run with it as STATUS, or ask a human." >&2
  exit 5
fi

if printf '%s' "$TRANSITION_ERR" | grep -qiE 'not (found|available|valid)|no transition|invalid|does not exist'; then
  echo "transition-to-code-review.sh: status '$TARGET' is not available for $KEY (current: ${CURRENT_STATUS:-?})." >&2
  echo "transition-to-code-review.sh: discover the real review status via JIRA MCP (available next transitions), then re-run with it as STATUS, or ask a human." >&2
  exit 5
fi

echo "transition-to-code-review.sh: acli transition failed for $KEY: $TRANSITION_ERR" >&2
exit 3
