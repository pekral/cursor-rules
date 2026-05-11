#!/usr/bin/env bash
# load-issue.sh — single deterministic entry point for loading JIRA issue context.
#
# Usage:
#   load-issue.sh <KEY|URL>
#
# Accepts:
#   - a bare issue key, e.g. ECOMAIL-1234
#   - a /browse/<KEY> URL, e.g. https://ecomail.atlassian.net/browse/ECOMAIL-1234
#   - any JIRA URL containing ?selectedIssue=<KEY> (atlOrigin and other query
#     params are tolerated and ignored)
#
# Emits one JSON document on stdout with the following stable shape:
#
#   {
#     "key", "url", "summary", "status", "issueType", "priority",
#     "assignee", "reporter", "creator", "created", "updated",
#     "resolution", "resolutionDate", "dueDate", "environment",
#     "labels", "components", "fixVersions",
#     "parent":  { "key", "summary", "status" } | null,
#     "project": { "key", "name", "typeKey" }   | null,
#     "watchCount": <int>,
#     "timeTracking": { "originalEstimate", "remainingEstimate", "timeSpent", "ratio" },
#     "descriptionAdf":  <raw ADF or null>,
#     "descriptionText": "<flattened plain text>",
#     "descriptionMediaRefs": [ { "adfId", "altText" } ],
#     "issueLinks":  [ { "id", "type", "direction", "verb", "linkedKey", "linkedSummary", "linkedStatus", "linkedType" } ],
#     "subtasks":    [ { "key", "summary", "status", "type" } ],
#     "comments":    [ { "author", "body", "created", "visibility" } ],
#     "attachments": [ { "id", "name", "size", "mimeType", "contentUrl", "author", "created" } ],
#     "customFields":  { "customfield_XXXXX": <parsed value>, … },
#     "devSummary":    { "pullRequestCount", "branchCount", "commitCount", "state", "isStale", "byInstance" } | null,
#     "pullRequests":  [ { "number", "title", "url", "state", "headRefName", "baseRefName", "isDraft", "mergedAt", "author" } ]
#   }
#
# Notes:
#   - `customFields` runs every customfield_* value through a universal Java/Groovy
#     toString unwrap: any string that starts with `{` and contains `json={…}` is
#     parsed back into JSON. The leading-`{` anchor keeps the unwrap from firing
#     on free-text fields that happen to mention `json={…}` in prose. Primitives,
#     arrays, and already-structured objects pass through unchanged. No
#     site-specific field IDs are hardcoded.
#   - `descriptionMediaRefs[]` carries only `adfId` + `altText`. Correlating ADF
#     media nodes back to entries in `attachments[]` requires an additional
#     Atlassian Cloud Media API call, which is out of scope for this script;
#     consumers should join on `attachments[]` themselves when needed.
#   - `devSummary` is a convenience projection derived from
#     customFields[$JIRA_DEV_SUMMARY_FIELD] (default `customfield_10000`,
#     overridable via the JIRA_DEV_SUMMARY_FIELD env var). The shape stays
#     identical across JIRA sites because it's computed, not field-id-bound.
#   - The Atlassian CLI `--paginate` flag emits a JSON stream (one document per
#     page) instead of a single document. We slurp with `jq -s` to merge pages.
#   - `pullRequests` are resolved via `gh search prs <KEY>` and may include PRs
#     across any GitHub repo the current `gh` auth can see.
#
# Known limitations (intentionally out of scope, fall back to JIRA MCP):
#   - issue changelog (`expand=changelog`)
#   - available next transitions
#   - friendly custom-field names (`expand=names`)
#
# Exit codes:
#   1  usage error (missing or unparseable argument)
#   2  missing required tool (acli, jq)
#   3  JIRA fetch failed
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: load-issue.sh <KEY|URL>

  KEY    bare JIRA work-item key (e.g. ECOMAIL-1234)
  URL    /browse/<KEY> URL or any URL with ?selectedIssue=<KEY>

Env:
  JIRA_SITE                  override the JIRA host (e.g. ecomail.atlassian.net)
  JIRA_DEV_SUMMARY_FIELD     customfield id feeding devSummary (default: customfield_10000)
EOF
}

if [[ $# -ne 1 || -z "${1:-}" ]]; then
  usage
  exit 1
fi

INPUT="$1"

for bin in acli jq; do
  if ! command -v "$bin" >/dev/null 2>&1; then
    echo "load-issue.sh: required tool not found: $bin" >&2
    exit 2
  fi
done

extract_key_from_url() {
  local url="$1"
  local key
  key="$(printf '%s' "$url" | grep -oE '[?&]selectedIssue=[A-Z][A-Z0-9_]+-[0-9]+' | head -n1 | sed -E 's/^[?&]selectedIssue=//')"
  if [[ -n "$key" ]]; then
    printf '%s' "$key"
    return 0
  fi
  key="$(printf '%s' "$url" | grep -oE '/browse/[A-Z][A-Z0-9_]+-[0-9]+' | head -n1 | sed -E 's#^/browse/##')"
  if [[ -n "$key" ]]; then
    printf '%s' "$key"
    return 0
  fi
  return 1
}

extract_host_from_url() {
  local url="$1"
  printf '%s' "$url" | sed -nE 's#^https?://([^/]+)/.*#\1#p'
}

KEY=""
HOST_FROM_URL=""

if [[ "$INPUT" =~ ^[A-Z][A-Z0-9_]+-[0-9]+$ ]]; then
  KEY="$INPUT"
elif [[ "$INPUT" =~ ^https?:// ]]; then
  if ! KEY="$(extract_key_from_url "$INPUT")"; then
    echo "load-issue.sh: could not extract a JIRA key from URL: $INPUT" >&2
    exit 1
  fi
  HOST_FROM_URL="$(extract_host_from_url "$INPUT")"
else
  echo "load-issue.sh: argument must be a bare key or a URL: $INPUT" >&2
  exit 1
fi

resolve_host() {
  if [[ -n "$HOST_FROM_URL" ]]; then
    printf '%s' "$HOST_FROM_URL"
    return 0
  fi
  if [[ -n "${JIRA_SITE:-}" ]]; then
    printf '%s' "$JIRA_SITE"
    return 0
  fi
  local config="${HOME}/.config/acli/jira_config.yaml"
  if [[ -f "$config" ]]; then
    awk '/^current_profile:/ { cp=$2 } /^[[:space:]]*-[[:space:]]*site:/ { site=$3 } /^[[:space:]]*cloud_id:/ { if ($2 ":" account_id == cp) { print site; exit } cid=$2 } /^[[:space:]]*account_id:/ { account_id=$2; if (cid ":" account_id == cp) { print site; exit } }' "$config" | head -n1
  fi
}

HOST="$(resolve_host || true)"
DEV_FIELD="${JIRA_DEV_SUMMARY_FIELD:-customfield_10000}"

VIEW_JSON="$(acli jira workitem view "$KEY" --fields '*all' --json 2>/dev/null || true)"
if [[ -z "$VIEW_JSON" ]] || ! printf '%s' "$VIEW_JSON" | jq -e . >/dev/null 2>&1; then
  echo "load-issue.sh: failed to fetch JIRA issue $KEY" >&2
  exit 3
fi

COMMENTS_JSON="$(acli jira workitem comment list --key "$KEY" --json --paginate 2>/dev/null | jq -s '{ comments: ([ .[].comments // [] ] | add // []) }' 2>/dev/null || printf '{"comments": []}')"

PRS_JSON='[]'
if command -v gh >/dev/null 2>&1; then
  PRS_JSON="$(gh search prs "$KEY" \
      --json number,title,url,state,headRefName,baseRefName,isDraft,mergedAt,author \
      --limit 50 2>/dev/null || printf '[]')"
  if ! printf '%s' "$PRS_JSON" | jq -e . >/dev/null 2>&1; then
    PRS_JSON='[]'
  fi
fi

jq -n \
  --arg key "$KEY" \
  --arg host "$HOST" \
  --arg devField "$DEV_FIELD" \
  --argjson view "$VIEW_JSON" \
  --argjson commentsResp "$COMMENTS_JSON" \
  --argjson prs "$PRS_JSON" '
def tryParseTrimEnd:
  . as $s
  | { s: $s, parsed: null }
  | until(.parsed != null or (.s | length) == 0;
      .s |= .[0:length-1]
      | .parsed = (.s | fromjson?))
  | .parsed;

def unwrapJavaToString:
  if type == "string" and test("^\\{.*json=\\{") then
    (capture("json=(?<j>\\{.*\\})") | .j) as $candidate
    | (($candidate | fromjson?) // ($candidate | tryParseTrimEnd) // .)
  else .
  end;

def adfText:
  if type != "object" then ""
  elif .type == "text" then (.text // "")
  elif .type == "hardBreak" then "\n"
  elif (.type // "") | IN("paragraph","heading","bulletList","orderedList","listItem","blockquote","codeBlock")
  then
    ((.content // []) | map(adfText) | join("")) + "\n"
  else
    ((.content // []) | map(adfText) | join(""))
  end;

def adfMedia:
  if type != "object" then []
  elif .type == "media" then
    [ { adfId: (.attrs.id // null),
        altText: (.attrs.alt // null) } ]
  else
    ((.content // []) | map(adfMedia) | add // [])
  end;

($view.fields // {}) as $f
| ($f.attachment // []) as $att
| ($f.subtasks // []) as $sub
| ($f.issuelinks // []) as $links
| (($f.comment.comments // []) | map({key: .id, value: {created: .created, visibility: (.visibility.value // null)}}) | from_entries) as $viewCommentIdx
| ($f | to_entries
       | map(select(.key | startswith("customfield_")))
       | map({ key: .key, value: (.value | unwrapJavaToString) })
       | from_entries) as $cf
| ($cf[$devField] // null) as $devRaw
| (if ($devRaw | type) == "object" and ($devRaw.cachedValue.summary // null) != null
    then $devRaw.cachedValue.summary
    else null
   end) as $devCached
| ($f.description // null) as $desc
| (if $desc == null then "" else ($desc | adfText) end) as $descText
| (if $desc == null then [] else ($desc | adfMedia) end) as $descMedia
| {
    key: $key,
    url: (if $host != "" then "https://" + $host + "/browse/" + $key else null end),
    summary: ($f.summary // null),
    status: ($f.status.name // null),
    issueType: ($f.issuetype.name // null),
    priority: ($f.priority.name // null),
    assignee: ($f.assignee.displayName // null),
    reporter: ($f.reporter.displayName // null),
    creator: ($f.creator.displayName // null),
    created: ($f.created // null),
    updated: ($f.updated // null),
    resolution: ($f.resolution.name // null),
    resolutionDate: ($f.resolutiondate // null),
    dueDate: ($f.duedate // null),
    environment: ($f.environment // null),
    labels: ($f.labels // []),
    components: ($f.components // [] | map(.name)),
    fixVersions: ($f.fixVersions // [] | map(.name)),
    parent: (if $f.parent then { key: $f.parent.key, summary: ($f.parent.fields.summary // null), status: ($f.parent.fields.status.name // null) } else null end),
    project: (if $f.project then { key: $f.project.key, name: $f.project.name, typeKey: ($f.project.projectTypeKey // null) } else null end),
    watchCount: ($f.watches.watchCount // 0),
    timeTracking: {
      originalEstimate: ($f.timetracking.originalEstimate // null),
      remainingEstimate: ($f.timetracking.remainingEstimate // null),
      timeSpent: ($f.timetracking.timeSpent // null),
      ratio: ($f.workratio // null)
    },
    descriptionAdf: $desc,
    descriptionText: ($descText | gsub("\n\n+"; "\n\n") | sub("\n+$"; "")),
    descriptionMediaRefs: $descMedia,
    issueLinks: ($links | map(
      if .outwardIssue then
        { id: .id, type: (.type.name // null), direction: "outward",
          verb: (.type.outward // null),
          linkedKey: .outwardIssue.key,
          linkedSummary: (.outwardIssue.fields.summary // null),
          linkedStatus: (.outwardIssue.fields.status.name // null),
          linkedType: (.outwardIssue.fields.issuetype.name // null) }
      else
        { id: .id, type: (.type.name // null), direction: "inward",
          verb: (.type.inward // null),
          linkedKey: (.inwardIssue.key // null),
          linkedSummary: (.inwardIssue.fields.summary // null),
          linkedStatus: (.inwardIssue.fields.status.name // null),
          linkedType: (.inwardIssue.fields.issuetype.name // null) }
      end)),
    subtasks: ($sub | map({
      key: .key,
      summary: (.fields.summary // null),
      status: (.fields.status.name // null),
      type: (.fields.issuetype.name // null)
    })),
    comments: ($commentsResp.comments // [] | map(. as $c | {
      author: (if ($c.author | type) == "object" then ($c.author.displayName // null) else $c.author end),
      body: (if ($c.body | type) == "object" then ($c.body | adfText) else $c.body end),
      created: ($c.created // $viewCommentIdx[$c.id].created // null),
      visibility: (if ($c.visibility | type) == "object" then $c.visibility.value else ($c.visibility // $viewCommentIdx[$c.id].visibility) end)
    })),
    attachments: ($att | map({
      id: (.id // null),
      name: (.filename // null),
      size: (.size // null),
      mimeType: (.mimeType // null),
      contentUrl: (.content // null),
      author: (if (.author | type) == "object" then (.author.displayName // null) else .author end),
      created: (.created // null)
    })),
    customFields: $cf,
    devSummary: (if $devCached == null then null else {
      pullRequestCount: ($devCached.pullrequest.overall.count // 0),
      branchCount: ($devCached.branch.overall.count // 0),
      commitCount: ($devCached.repository.overall.count // 0),
      state: ($devCached.pullrequest.overall.state // null),
      isStale: (($devCached.pullrequest.overall.stateCount // 0) == 0),
      byInstance: ($devCached.pullrequest.byInstanceType // null)
    } end),
    pullRequests: ($prs | map({
      number: .number,
      title: .title,
      url: .url,
      state: .state,
      headRefName: .headRefName,
      baseRefName: .baseRefName,
      isDraft: .isDraft,
      mergedAt: .mergedAt,
      author: (.author.login // null)
    }))
  }
'
