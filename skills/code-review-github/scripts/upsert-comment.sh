#!/usr/bin/env bash
# upsert-comment.sh — idempotent upsert of a GitHub issue / PR comment, keyed
# by the actor running this script. Used by CR-track skills so each reviewer
# identity owns exactly one comment per (issue|PR, actor) and follow-up runs
# edit it in place instead of stacking new comments.
#
# Usage:
#   upsert-comment.sh <NUMBER|URL> <BODY_FILE> [<MARKER_KEY>]
#   <body-producer> | upsert-comment.sh <NUMBER|URL> - [<MARKER_KEY>]
#
# Inputs:
#   NUMBER|URL  Bare GitHub issue / PR number (resolved against the current
#               git remote) or a full github.com URL containing /issues/<N> or
#               /pull/<N>. The optional `www.` host prefix is tolerated.
#   BODY_FILE   Path to a file holding the comment body, or `-` to read from
#               stdin. The body must already be in the target tracker markup
#               (GitHub Markdown).
#   MARKER_KEY  Optional. Marker namespace, defaults to `cr-comment`. CR
#               wrappers (`code-review-github`, `code-review-jira`, `pr-summary`)
#               leave it at the default; `process-code-review` passes
#               `cr-status` so its resolved-items follow-up owns a separate
#               per-actor comment that gets edited in place across loop runs
#               instead of stacking on top of the CR comment.
#
# Behavior:
#   1. Detect the actor login via `gh api user --jq .login`.
#   2. Append a hidden marker `<!-- <MARKER_KEY>:actor=<login> -->` to the body
#      (only when the body does not already carry the marker).
#   3. List the issue / PR comments and find the most recent one carrying the
#      same marker — that is the actor's prior comment in this namespace.
#   4. If found, PATCH it (`gh api repos/<nwo>/issues/comments/<id>`).
#      Otherwise, POST a fresh comment.
#
# The marker stays at the bottom of the comment so it survives manual edits
# at the top. It is rendered by GitHub as an invisible HTML comment.
#
# Output:
#   The published comment URL on stdout. `action=created|updated` on stderr
#   for the calling skill to log in its summary line.
#
# Exit codes:
#   1  usage / argument error
#   2  missing required tool (gh, jq)
#   3  GitHub API call failed
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: upsert-comment.sh <NUMBER|URL> <BODY_FILE|-> [<MARKER_KEY>]

  NUMBER      bare GitHub issue or PR number (resolved against current git remote)
  URL         any github.com URL containing /issues/<N> or /pull/<N>
  BODY_FILE   path to a file containing the comment body, or `-` for stdin
  MARKER_KEY  optional marker namespace (default: cr-comment).
              Use `cr-status` from process-code-review so the resolved-items
              comment owns its own per-actor slot.
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

for bin in gh jq; do
  if ! command -v "$bin" >/dev/null 2>&1; then
    echo "upsert-comment.sh: required tool not found: $bin" >&2
    exit 2
  fi
done

resolve_repo_from_git() {
  local remote
  remote="$(git config --get remote.origin.url 2>/dev/null || true)"
  if [[ -z "$remote" ]]; then
    return 1
  fi
  printf '%s' "$remote" \
    | sed -E -e 's#^git@github\.com:#https://github.com/#' -e 's#\.git$##' \
    | sed -nE 's#^https?://github\.com/([^/]+)/([^/]+).*#\1 \2#p'
}

OWNER=""
REPO=""
NUMBER=""

if [[ "$INPUT" =~ ^https?://(www\.)?github\.com/ ]]; then
  parsed="$(printf '%s' "$INPUT" | sed -nE 's#^https?://(www\.)?github\.com/([^/]+)/([^/]+)/(issues|pull)/([0-9]+).*#\2 \3 \5#p' || true)"
  if [[ -z "$parsed" ]]; then
    echo "upsert-comment.sh: could not extract issue/PR from URL: $INPUT" >&2
    exit 1
  fi
  OWNER="$(printf '%s' "$parsed" | awk '{print $1}')"
  REPO="$(printf '%s' "$parsed"  | awk '{print $2}')"
  NUMBER="$(printf '%s' "$parsed" | awk '{print $3}')"
elif [[ "$INPUT" =~ ^[0-9]+$ ]]; then
  NUMBER="$INPUT"
  parsed="$(resolve_repo_from_git || true)"
  if [[ -z "$parsed" ]]; then
    echo "upsert-comment.sh: cannot resolve repo from git remote — pass a full URL instead" >&2
    exit 1
  fi
  OWNER="$(printf '%s' "$parsed" | awk '{print $1}')"
  REPO="$(printf '%s' "$parsed"  | awk '{print $2}')"
else
  echo "upsert-comment.sh: argument must be a bare number or a github.com URL: $INPUT" >&2
  exit 1
fi

NWO="${OWNER}/${REPO}"

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

ACTOR="$(gh api user --jq .login 2>/dev/null || true)"
if [[ -z "$ACTOR" ]]; then
  echo "upsert-comment.sh: failed to resolve current GitHub actor — is gh authenticated?" >&2
  exit 3
fi

MARKER="<!-- ${MARKER_KEY}:actor=${ACTOR} -->"

if ! grep -Fq "$MARKER" <<<"$BODY"; then
  BODY="${BODY}

${MARKER}"
fi

# `gh api .../issues/<N>/comments` works for both issues and PRs — GitHub treats
# PR conversation comments as issue comments under the hood. `--paginate` emits
# one JSON array per page concatenated without a wrapping array, so the jq
# pipeline below uses `-s` (slurp) and `.[][]` to flatten every page into a
# single stream before filtering / sorting; otherwise the `last` would be
# computed page-locally and miss the marker comment on issues with > 30
# comments.
COMMENTS_JSON="$(gh api --paginate "repos/${NWO}/issues/${NUMBER}/comments" || true)"
if [[ -z "$COMMENTS_JSON" ]]; then
  echo "upsert-comment.sh: failed to list comments on ${NWO}#${NUMBER}" >&2
  exit 3
fi

EXISTING_ID="$(printf '%s' "$COMMENTS_JSON" \
  | jq -s -r --arg marker "$MARKER" '
      [ .[][] | select(.body | contains($marker)) ]
      | sort_by(.updated_at)
      | last
      | (.id // empty)
    ')"

if [[ -n "$EXISTING_ID" ]]; then
  RESPONSE="$(printf '%s' "$BODY" | gh api \
    "repos/${NWO}/issues/comments/${EXISTING_ID}" \
    -X PATCH \
    -f body=@- \
    || true)"
  if [[ -z "$RESPONSE" ]]; then
    echo "upsert-comment.sh: PATCH failed on comment ${EXISTING_ID}" >&2
    exit 3
  fi
  printf '%s' "$RESPONSE" | jq -r '.html_url'
  echo "action=updated id=${EXISTING_ID}" >&2
else
  RESPONSE="$(printf '%s' "$BODY" | gh api \
    "repos/${NWO}/issues/${NUMBER}/comments" \
    -X POST \
    -f body=@- \
    || true)"
  if [[ -z "$RESPONSE" ]]; then
    echo "upsert-comment.sh: POST failed on ${NWO}#${NUMBER}" >&2
    exit 3
  fi
  printf '%s' "$RESPONSE" | jq -r '.html_url'
  NEW_ID="$(printf '%s' "$RESPONSE" | jq -r '.id')"
  echo "action=created id=${NEW_ID}" >&2
fi
