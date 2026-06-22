#!/usr/bin/env bash
# upsert-comment.sh — always-new JIRA issue comment per CR run, keyed by the
# acli actor running this script. Used by CR-track skills so each CR run posts
# a fresh comment (visible at the bottom of the JIRA thread) instead of
# editing a prior comment in place. The hidden anchor marker is still appended
# to the body for traceability but no longer drives a lookup or update.
#
# Usage:
#   upsert-comment.sh <KEY|URL> <BODY_FILE> [<MARKER_KEY>]
#   <body-producer> | upsert-comment.sh <KEY|URL> - [<MARKER_KEY>]
#
# Inputs:
#   KEY|URL     Bare JIRA issue key (e.g. ECOMAIL-1234), a /browse/<KEY> URL,
#               or any URL containing ?selectedIssue=<KEY>.
#   BODY_FILE   Path to a file holding the JIRA Wiki Markup body, or `-` to
#               read from stdin.
#   MARKER_KEY  Optional. Marker namespace, defaults to `cr-comment`. CR
#               wrappers (`code-review-github`, `code-review-jira`, `pr-summary`)
#               leave it at the default; `process-code-review` passes
#               `cr-status` so its resolved-items follow-up owns a separate
#               per-actor JIRA comment (also always-new per run).
#
# Behavior:
#   1. Detect the actor identity from `acli jira auth status` (the
#      authenticated account email), normalised to a slug.
#   2. Append a hidden marker `{anchor:<MARKER_KEY>-actor-<slug>}` to the body
#      (only when the body does not already carry the marker). The marker is
#      placed at the **bottom** of the body so the JIRA UI keeps the comment's
#      first line (typically the `*Authors:*` line rendered by `pr-summary`)
#      flush at the top — prepending would render an empty paragraph above
#      it. The `{anchor:}` macro is invisible in the JIRA UI but stays
#      grep-able in the raw body returned by the REST API.
#   3. Always create a fresh comment (`acli jira workitem comment create`).
#      No lookup, no update — every CR run adds a new comment so the
#      chronological sequence of comments is the audit trail.
#
# Output:
#   The published comment URL on stdout. `action=created` on stderr
#   for the calling skill to log in its summary line.
#
# Exit codes:
#   1  usage / argument error
#   2  missing required tool (acli, jq)
#   3  JIRA API call failed
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: upsert-comment.sh <KEY|URL> <BODY_FILE|-> [<MARKER_KEY>]

  KEY         JIRA issue key (e.g. ECOMAIL-1234)
  URL         /browse/<KEY> URL or any URL containing ?selectedIssue=<KEY>
  BODY_FILE   path to a file containing the comment body, or `-` for stdin
  MARKER_KEY  optional marker namespace (default: cr-comment).
              Use `cr-status` from process-code-review so the resolved-items
              comment is identifiable as a status post.
EOF
}

if [[ $# -lt 2 || $# -gt 3 || -z "${1:-}" || -z "${2:-}" ]]; then
  usage
  exit 1
fi

INPUT="$1"
BODY_SRC="$2"
MARKER_KEY="${3:-cr-comment}"

if [[ ! "$MARKER_KEY" =~ ^[a-z][a-z0-9-]*$ ]]; then
  echo "upsert-comment.sh: MARKER_KEY must match [a-z][a-z0-9-]* — got: $MARKER_KEY" >&2
  exit 1
fi

for bin in acli jq; do
  if ! command -v "$bin" >/dev/null 2>&1; then
    echo "upsert-comment.sh: required tool not found: $bin" >&2
    exit 2
  fi
done

KEY=""
if [[ "$INPUT" =~ ^[A-Z][A-Z0-9_]+-[0-9]+$ ]]; then
  KEY="$INPUT"
elif [[ "$INPUT" == *"/browse/"* ]]; then
  KEY="$(printf '%s' "$INPUT" | sed -nE 's#.*/browse/([A-Z][A-Z0-9_]+-[0-9]+).*#\1#p')"
elif [[ "$INPUT" == *"selectedIssue="* ]]; then
  KEY="$(printf '%s' "$INPUT" | sed -nE 's#.*selectedIssue=([A-Z][A-Z0-9_]+-[0-9]+).*#\1#p')"
fi

if [[ -z "$KEY" ]]; then
  echo "upsert-comment.sh: could not extract JIRA key from input: $INPUT" >&2
  exit 1
fi

if [[ "$BODY_SRC" == "-" ]]; then
  BODY="$(cat)"
else
  if [[ ! -r "$BODY_SRC" ]]; then
    echo "upsert-comment.sh: cannot read body file: $BODY_SRC" >&2
    exit 1
  fi
  BODY="$(cat "$BODY_SRC")"
fi

if [[ -z "$BODY" ]]; then
  echo "upsert-comment.sh: refusing to publish an empty comment" >&2
  exit 1
fi

# Resolve the actor identity and site from `acli jira auth status`. The
# installed acli build prints them as human-readable lines:
#   ✓ Authenticated
#     Site: your-org.atlassian.net
#     Email: someone@example.com
AUTH_STATUS="$(acli jira auth status 2>/dev/null || true)"
ACTOR_ID="$(printf '%s' "$AUTH_STATUS" | awk -F': *' 'tolower($0) ~ /email:/ { gsub(/[[:space:]]+$/, "", $2); print $2; exit }')"
if [[ -z "$ACTOR_ID" ]]; then
  echo "upsert-comment.sh: failed to resolve current JIRA actor — is acli authenticated? (run: acli jira auth status)" >&2
  exit 3
fi
SITE="$(printf '%s' "$AUTH_STATUS" | awk -F': *' 'tolower($0) ~ /site:/ { gsub(/[[:space:]]+$/, "", $2); print $2; exit }')"

# Normalise to an anchor-safe slug (lowercase alnum + dashes).
ACTOR_SLUG="$(printf '%s' "$ACTOR_ID" | tr '[:upper:]' '[:lower:]' | tr -c 'a-z0-9' '-' | sed -E 's#-+#-#g; s#^-|-$##g')"
MARKER="{anchor:${MARKER_KEY}-actor-${ACTOR_SLUG}}"

# Append at the bottom of the body so the JIRA UI keeps the first content
# line (typically the `*Authors:*` line) flush at the top. Prepending would
# render an empty paragraph above it because `{anchor:…}` on its own line
# produces an empty block element in the rendered comment.
if ! grep -Fq "$MARKER" <<<"$BODY"; then
  BODY="${BODY}

${MARKER}"
fi

# acli reads the comment body from a file (no stdin flag in the current build).
BODY_FILE_TMP="$(mktemp)"
trap 'rm -f "$BODY_FILE_TMP"' EXIT
printf '%s' "$BODY" > "$BODY_FILE_TMP"

# Always post a fresh comment — never look up or edit a prior one. The
# chronological sequence of comments is the audit trail across CR runs.
# `comment list` is no longer used; `list_comments` and `find_marked_id`
# helpers are removed. The marker in the body is retained for traceability
# (grep-able via the REST API) but does not drive any lookup.
if ! acli jira workitem comment create --key "$KEY" --body-file "$BODY_FILE_TMP" --json >/dev/null 2>&1; then
  echo "upsert-comment.sh: acli comment create failed on $KEY" >&2
  exit 3
fi

# Re-list comments to resolve the new comment id so stdout carries a deep-link
# URL. The `create --json` shape varies across acli builds, so we match the
# just-written marker deterministically after the fact.
list_comments() {
  local raw
  raw="$(acli jira workitem comment list --key "$KEY" --json --paginate 2>/dev/null)" || return 1
  printf '%s' "$raw" | jq -s '{ comments: ([ .[].comments // [] ] | add // []) }' 2>/dev/null
}

find_marked_id() {
  printf '%s' "$1" \
    | jq -r --arg marker "$MARKER" '
        (.comments // [])
        | map(select((.body | tostring) | contains($marker)))
        | sort_by(.updated // .created)
        | last
        | (.id // empty)
      ' 2>/dev/null || true
}

if ! COMMENTS_JSON="$(list_comments)"; then
  echo "upsert-comment.sh: failed to list comments on $KEY after create — returning issue URL" >&2
  echo "https://${SITE}/browse/${KEY}"
  echo "action=created" >&2
  exit 0
fi

NEW_ID="$(find_marked_id "$COMMENTS_JSON")"
if [[ -n "$NEW_ID" ]]; then
  echo "https://${SITE}/browse/${KEY}?focusedCommentId=${NEW_ID}"
else
  echo "https://${SITE}/browse/${KEY}"
fi
echo "action=created id=${NEW_ID}" >&2
