#!/usr/bin/env bash
# load-issue.sh — single deterministic entry point for loading GitHub issue or PR context.
#
# Usage:
#   load-issue.sh <NUMBER|URL>
#
# Accepts:
#   - a bare issue/PR number, e.g. 445 (uses the current git remote to resolve the repo)
#   - a GitHub URL, e.g. https://github.com/<owner>/<repo>/issues/445
#   - a GitHub URL, e.g. https://github.com/<owner>/<repo>/pull/123
#   - any GitHub URL containing /issues/<N> or /pull/<N>
#     (the optional `www.` host prefix is tolerated, e.g. https://www.github.com/...)
#
# Emits one JSON document on stdout with the following stable shape:
#
#   {
#     "kind": "issue" | "pr",
#     "number": <int>,
#     "url": <string>,
#     "repo": { "owner", "name", "nameWithOwner" },
#     "title", "body", "state", "stateReason",
#     "author", "assignees", "labels", "milestone",
#     "createdAt", "updatedAt", "closedAt",
#     "comments":     [ { "author", "body", "createdAt", "updatedAt", "url" } ],
#     "reactionsCount": <int>,
#
#     # issue-only (null / [] when kind == "pr")
#     "closingPullRequests": [ { "number", "title", "url", "state" } ],
#
#     # pr-only (null / [] when kind == "issue")
#     "baseRefName", "headRefName", "baseRefOid", "headRefOid",
#     "isDraft", "mergeable", "mergeStateStatus",
#     "mergedAt", "mergedBy", "mergeCommit",
#     "additions", "deletions", "changedFiles",
#     "files":             [ { "path", "additions", "deletions" } ],
#     "commits":           [ { "oid", "messageHeadline", "authoredDate", "authors" } ],
#     "reviews":           [ { "author", "state", "body", "submittedAt", "url" } ],
#     "reviewRequests":    [ <login> ],
#     "reviewDecision":    <string|null>,
#     "statusCheckRollup": [ { "context", "state", "description", "targetUrl" } ],
#     "closingIssues":     [ { "number", "title", "url", "state" } ]
#   }
#
# Notes:
#   - The script is the single deterministic source of GitHub context. Skills must
#     never call `gh issue view`, `gh pr view`, or `gh api /repos/.../issues/...`
#     directly: changes to the JSON shape happen here, in one place.
#   - When the input is a bare number, the repo is resolved from the current
#     directory's git remote (`origin`). Provide a full URL to load issues/PRs
#     from any other repo the current `gh` auth can see.
#   - The kind ("issue" vs "pr") is detected from the URL path when present, and
#     otherwise inferred by trying `gh issue view` first and falling back to
#     `gh pr view`. GitHub's REST issue endpoint exposes both numbers, but the
#     `gh` CLI uses different field sets, so the kind drives which command runs.
#   - `closingPullRequests` (for issues) is sourced from
#     `closedByPullRequestsReferences` and surfaces the PRs that will close the
#     issue when merged. `closingIssues` (for PRs) lists issues the PR closes.
#
# Known limitations (intentionally out of scope, fall back to GitHub MCP):
#   - Review thread / line-anchored review comments (`gh api .../pulls/<N>/comments`)
#   - Per-commit check runs and detailed CI logs
#   - Attachment binary contents (only URLs and metadata are returned)
#
# Exit codes:
#   1  usage error (missing or unparseable argument)
#   2  missing required tool (gh, jq)
#   3  GitHub fetch failed
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: load-issue.sh <NUMBER|URL>

  NUMBER  bare GitHub issue or PR number (e.g. 445). Resolved against the
          current directory's git remote.
  URL     any github.com URL containing /issues/<N> or /pull/<N>
EOF
}

if [[ $# -ne 1 || -z "${1:-}" ]]; then
  usage
  exit 1
fi

INPUT="$1"

for bin in gh jq; do
  if ! command -v "$bin" >/dev/null 2>&1; then
    echo "load-issue.sh: required tool not found: $bin" >&2
    exit 2
  fi
done

extract_from_url() {
  # Echoes: "<owner> <repo> <number> <kind>"
  local url="$1"
  local path
  path="$(printf '%s' "$url" | sed -nE 's#^https?://(www\.)?github\.com/([^/]+)/([^/]+)/(issues|pull)/([0-9]+).*#\2 \3 \5 \4#p')"
  if [[ -z "$path" ]]; then
    return 1
  fi
  # Normalize "pull" -> "pr"
  printf '%s' "$path" | awk '{
    kind = $4
    if (kind == "pull") kind = "pr"
    else if (kind == "issues") kind = "issue"
    print $1, $2, $3, kind
  }'
}

resolve_repo_from_git() {
  local remote
  remote="$(git config --get remote.origin.url 2>/dev/null || true)"
  if [[ -z "$remote" ]]; then
    return 1
  fi
  # Handle both git@github.com:owner/repo(.git) and https://github.com/owner/repo(.git)
  printf '%s' "$remote" \
    | sed -E -e 's#^git@github\.com:#https://github.com/#' -e 's#\.git$##' \
    | sed -nE 's#^https?://github\.com/([^/]+)/([^/]+).*#\1 \2#p'
}

OWNER=""
REPO=""
NUMBER=""
KIND=""

if [[ "$INPUT" =~ ^https?://(www\.)?github\.com/ ]]; then
  parsed="$(extract_from_url "$INPUT" || true)"
  if [[ -z "$parsed" ]]; then
    echo "load-issue.sh: could not extract issue/PR from URL: $INPUT" >&2
    exit 1
  fi
  OWNER="$(printf '%s' "$parsed" | awk '{print $1}')"
  REPO="$(printf '%s' "$parsed"  | awk '{print $2}')"
  NUMBER="$(printf '%s' "$parsed" | awk '{print $3}')"
  KIND="$(printf '%s' "$parsed"   | awk '{print $4}')"
elif [[ "$INPUT" =~ ^[0-9]+$ ]]; then
  NUMBER="$INPUT"
  parsed="$(resolve_repo_from_git || true)"
  if [[ -z "$parsed" ]]; then
    echo "load-issue.sh: cannot resolve repo from git remote — pass a full URL instead" >&2
    exit 1
  fi
  OWNER="$(printf '%s' "$parsed" | awk '{print $1}')"
  REPO="$(printf '%s' "$parsed"  | awk '{print $2}')"
else
  echo "load-issue.sh: argument must be a bare number or a github.com URL: $INPUT" >&2
  exit 1
fi

NWO="${OWNER}/${REPO}"

ISSUE_FIELDS="assignees,author,body,closed,closedAt,closedByPullRequestsReferences,comments,createdAt,labels,milestone,number,reactionGroups,state,stateReason,title,updatedAt,url"
PR_FIELDS="additions,assignees,author,baseRefName,baseRefOid,body,changedFiles,closed,closedAt,closingIssuesReferences,comments,commits,createdAt,deletions,files,headRefName,headRefOid,isDraft,labels,latestReviews,maintainerCanModify,mergeCommit,mergeStateStatus,mergeable,mergedAt,mergedBy,milestone,number,reactionGroups,reviewDecision,reviewRequests,reviews,state,statusCheckRollup,title,updatedAt,url"

ISSUE_JSON='null'
PR_JSON='null'

if [[ -z "$KIND" ]]; then
  # Try PR first, then issue. GitHub shares the issue/PR number space and
  # `gh issue view <PR-number>` succeeds with the issue-level projection,
  # which would hide all PR-only fields. `gh pr view` returns non-zero on
  # non-PR numbers, so it is the correct discriminator.
  if PR_JSON="$(gh pr view "$NUMBER" --repo "$NWO" --json "$PR_FIELDS" 2>/dev/null)" && [[ -n "$PR_JSON" ]]; then
    KIND="pr"
  elif ISSUE_JSON="$(gh issue view "$NUMBER" --repo "$NWO" --json "$ISSUE_FIELDS" 2>/dev/null)" && [[ -n "$ISSUE_JSON" ]]; then
    KIND="issue"
  else
    echo "load-issue.sh: failed to fetch issue or PR #$NUMBER in $NWO" >&2
    exit 3
  fi
fi

if [[ "$KIND" == "issue" && "$ISSUE_JSON" == "null" ]]; then
  if ! ISSUE_JSON="$(gh issue view "$NUMBER" --repo "$NWO" --json "$ISSUE_FIELDS" 2>/dev/null)" || [[ -z "$ISSUE_JSON" ]]; then
    echo "load-issue.sh: failed to fetch issue #$NUMBER in $NWO" >&2
    exit 3
  fi
fi

if [[ "$KIND" == "pr" && "$PR_JSON" == "null" ]]; then
  if ! PR_JSON="$(gh pr view "$NUMBER" --repo "$NWO" --json "$PR_FIELDS" 2>/dev/null)" || [[ -z "$PR_JSON" ]]; then
    echo "load-issue.sh: failed to fetch PR #$NUMBER in $NWO" >&2
    exit 3
  fi
fi

if [[ "$KIND" == "issue" ]]; then
  PAYLOAD="$ISSUE_JSON"
else
  PAYLOAD="$PR_JSON"
fi

if ! printf '%s' "$PAYLOAD" | jq -e . >/dev/null 2>&1; then
  echo "load-issue.sh: unexpected non-JSON response from gh for #$NUMBER" >&2
  exit 3
fi

jq -n \
  --arg kind "$KIND" \
  --arg owner "$OWNER" \
  --arg repo "$REPO" \
  --argjson payload "$PAYLOAD" '
def total_reactions:
  ([ (.reactionGroups // [])[] | (.users.totalCount // .reactors.totalCount // 0) ] | add) // 0;

def map_comments:
  [ (. // [])[] | {
      author: (.author.login // null),
      body: (.body // ""),
      createdAt: (.createdAt // null),
      updatedAt: (.updatedAt // null),
      url: (.url // null)
  } ];

def map_labels:    [ (. // [])[] | (.name // null) ];
def map_assignees: [ (. // [])[] | (.login // null) ];
def map_files:     [ (. // [])[] | { path: (.path // null), additions: (.additions // 0), deletions: (.deletions // 0) } ];
def map_commits:
  [ (. // [])[] | {
      oid: (.oid // null),
      messageHeadline: (.messageHeadline // null),
      authoredDate: (.authoredDate // null),
      authors: [ (.authors // [])[] | (.login // .name // null) ]
  } ];
def map_reviews:
  [ (. // [])[] | {
      author: (.author.login // null),
      state: (.state // null),
      body: (.body // ""),
      submittedAt: (.submittedAt // null),
      url: (.url // null)
  } ];
def map_status_checks:
  [ (. // [])[] | {
      context: (.context // .name // null),
      state: (.state // .conclusion // .status // null),
      description: (.description // null),
      targetUrl: (.targetUrl // .detailsUrl // null)
  } ];
def map_refs:
  [ (. // [])[] | {
      number: (.number // null),
      title: (.title // null),
      url: (.url // null),
      state: (.state // null)
  } ];

$payload as $p
| {
    kind: $kind,
    number: ($p.number // null),
    url: ($p.url // null),
    repo: { owner: $owner, name: $repo, nameWithOwner: ($owner + "/" + $repo) },
    title: ($p.title // null),
    body: ($p.body // ""),
    state: ($p.state // null),
    stateReason: ($p.stateReason // null),
    author: ($p.author.login // null),
    assignees: ($p.assignees | map_assignees),
    labels: ($p.labels | map_labels),
    milestone: ($p.milestone.title // null),
    createdAt: ($p.createdAt // null),
    updatedAt: ($p.updatedAt // null),
    closedAt: ($p.closedAt // null),
    comments: ($p.comments | map_comments),
    reactionsCount: ($p | total_reactions),

    closingPullRequests: (if $kind == "issue"
      then ($p.closedByPullRequestsReferences | map_refs)
      else [] end),

    baseRefName: (if $kind == "pr" then ($p.baseRefName // null) else null end),
    headRefName: (if $kind == "pr" then ($p.headRefName // null) else null end),
    baseRefOid:  (if $kind == "pr" then ($p.baseRefOid  // null) else null end),
    headRefOid:  (if $kind == "pr" then ($p.headRefOid  // null) else null end),
    isDraft:     (if $kind == "pr" then ($p.isDraft     // null) else null end),
    mergeable:   (if $kind == "pr" then ($p.mergeable   // null) else null end),
    mergeStateStatus: (if $kind == "pr" then ($p.mergeStateStatus // null) else null end),
    mergedAt:    (if $kind == "pr" then ($p.mergedAt    // null) else null end),
    mergedBy:    (if $kind == "pr" then ($p.mergedBy.login // null) else null end),
    mergeCommit: (if $kind == "pr" then ($p.mergeCommit.oid  // null) else null end),
    additions:   (if $kind == "pr" then ($p.additions   // 0) else 0 end),
    deletions:   (if $kind == "pr" then ($p.deletions   // 0) else 0 end),
    changedFiles:(if $kind == "pr" then ($p.changedFiles// 0) else 0 end),
    files:           (if $kind == "pr" then ($p.files       | map_files)       else [] end),
    commits:         (if $kind == "pr" then ($p.commits     | map_commits)     else [] end),
    reviews:         (if $kind == "pr" then ($p.reviews     | map_reviews)     else [] end),
    reviewRequests:  (if $kind == "pr" then [ ($p.reviewRequests // [])[] | (.login // .slug // .name // null) ] else [] end),
    reviewDecision:  (if $kind == "pr" then ($p.reviewDecision // null) else null end),
    statusCheckRollup: (if $kind == "pr" then ($p.statusCheckRollup | map_status_checks) else [] end),
    closingIssues:   (if $kind == "pr" then ($p.closingIssuesReferences | map_refs) else [] end)
  }
'
