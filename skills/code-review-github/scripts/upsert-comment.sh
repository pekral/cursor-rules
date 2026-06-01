#!/usr/bin/env bash
# upsert-comment.sh — append-only GitHub issue / PR comment publisher used by
# CR-track skills. Every invocation POSTs a fresh comment, even when a prior
# marker-carrying comment exists, so each CR run owns its own self-contained
# entry instead of editing an earlier one in place. The hidden marker stays in
# the body so previous comments remain identifiable per actor + namespace, but
# the lookup-and-PATCH branch was removed by user request — see CHANGELOG.
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
#               `cr-status` so its resolved-items follow-up stays distinguishable
#               from the CR comment even though every run is now a fresh post.
#
# Behavior:
#   1. Detect the actor login via `gh api user --jq .login`.
#   2. Append a hidden marker `<!-- <MARKER_KEY>:actor=<login> -->` to the body
#      (only when the body does not already carry the marker).
#   3. POST a new comment via `gh api repos/<nwo>/issues/<N>/comments`. The
#      script never edits prior comments; every CR run produces a new entry.
#
# The marker stays at the bottom of the comment so it survives manual edits
# at the top. It is rendered by GitHub as an invisible HTML comment.
#
# Output:
#   The published comment URL on stdout. `action=created` on stderr for the
#   calling skill to log in its summary line — the value is always `created`
#   because the script no longer PATCHes existing comments.
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
              comment keeps its own per-actor identification even though every
              run is posted as a fresh comment.
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

# Resolve the current GitHub actor through `gh api user`. Earlier revisions
# discarded both stderr and the exit code, which collapsed every failure mode
# (expired token, rate limit, network blip) into the same misleading
# "is gh authenticated?" message. Capture stderr to surface the actual cause
# and retry up to three times so a single transient API hiccup does not abort
# the whole CR comment publish — see issue #519.
ACTOR_STDERR="$(mktemp)"
trap 'rm -f "$ACTOR_STDERR"' EXIT
ACTOR=""
ACTOR_ERR=""
for attempt in 1 2 3; do
  : > "$ACTOR_STDERR"
  if ACTOR="$(gh api user --jq .login 2>"$ACTOR_STDERR")" && [[ -n "$ACTOR" ]]; then
    break
  fi
  ACTOR=""
  ACTOR_ERR="$(cat "$ACTOR_STDERR")"
  [[ $attempt -lt 3 ]] && sleep 1
done

if [[ -z "$ACTOR" ]]; then
  if [[ -n "$ACTOR_ERR" ]]; then
    echo "upsert-comment.sh: failed to resolve current GitHub actor after 3 attempts: ${ACTOR_ERR}" >&2
  else
    echo "upsert-comment.sh: failed to resolve current GitHub actor — is gh authenticated? (run: gh auth status)" >&2
  fi
  exit 3
fi

MARKER="<!-- ${MARKER_KEY}:actor=${ACTOR} -->"

if ! grep -Fq "$MARKER" <<<"$BODY"; then
  BODY="${BODY}

${MARKER}"
fi

# Always POST a fresh comment. The lookup-and-PATCH branch was removed by user
# request — every CR run now owns its own self-contained comment so reviewers
# see one entry per run instead of an edited-in-place history. `gh api` body
# payloads are built via jq and fed through `--input -` so the body stays a
# string regardless of its content (a body that happens to be `true` or an
# integer would otherwise be coerced by `-F` type inference).
RESPONSE="$(jq -n --arg body "$BODY" '{body:$body}' | gh api \
  "repos/${NWO}/issues/${NUMBER}/comments" \
  -X POST \
  --input - \
  || true)"
if [[ -z "$RESPONSE" ]]; then
  echo "upsert-comment.sh: POST failed on ${NWO}#${NUMBER}" >&2
  exit 3
fi
printf '%s' "$RESPONSE" | jq -r '.html_url'
NEW_ID="$(printf '%s' "$RESPONSE" | jq -r '.id')"
echo "action=created id=${NEW_ID}" >&2
