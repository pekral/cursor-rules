#!/usr/bin/env bash
# load-issue.sh — single deterministic entry point for loading Bugsnag error context.
#
# Usage:
#   load-issue.sh <URL|ORG_SLUG/PROJECT_SLUG/ERROR_ID>
#
# Accepts:
#   - a Bugsnag dashboard URL, e.g.
#     https://app.bugsnag.com/<org-slug>/<project-slug>/errors/<error-id>?filters[...]
#     (the optional `www.` host prefix is tolerated)
#   - a slash triple, e.g. ecomail-dot-cz/ecomailapp-dot-cz-1/6a295f14b8b7f261a8ade4db
#
# Auth:
#   Reads a personal Data Access API token from the BUGSNAG_TOKEN env var
#   (BUGSNAG_AUTH_TOKEN is accepted as an alias). This is the organization
#   data-access token, NOT the per-project notifier API key. The token is never
#   read from a file and never written anywhere by this script.
#
# Emits one JSON document on stdout with the following stable shape:
#
#   {
#     "kind": "bugsnag-error",
#     "id": <string>,
#     "url": <string>,                # app.bugsnag.com dashboard URL
#     "apiUrl": <string>,             # api.bugsnag.com error URL
#     "organization": { "id", "slug", "name" },
#     "project":      { "id", "slug", "name", "type", "language" },
#     "title":      <string>,         # error class (parity with GitHub/JIRA "title")
#     "errorClass": <string>,
#     "message":    <string>,
#     "context":    <string|null>,    # e.g. "PUT /lists/4/update-subscriber"
#     "status":     <string>,         # open | fixed | ignored | snoozed
#     "severity":   <string>,
#     "events":     <int>,            # occurrence count
#     "users":      <int>,
#     "firstSeen", "lastSeen", "createdAt": <string|null>,
#     "assignedCollaboratorId", "assignedTeamId": <string|null>,
#     "releaseStages": [ <string> ],
#     "linkedIssues":  [ { "type", "number", "url" } ],   # e.g. github-issues #25280
#     "commentCount": <int>,
#     "comments":      [ { "author", "email", "body", "createdAt", "updatedAt" } ],
#     "groupingFields": { "errorClass", "file" },
#     "latestEvent": {
#       "id", "receivedAt", "context", "unhandled", "severity",
#       "errorClass", "message",
#       "app":    { "id", "version", "releaseStage", "type" },
#       "device": { "osName", "osVersion", "runtimeVersions" },
#       "request":{ "httpMethod", "url", "clientIp" },
#       "user":   { "id", "name", "email" },
#       "stacktrace":  [ { "file", "line", "method", "inProject" } ],
#       "breadcrumbs": [ { "timestamp", "name", "type" } ]
#     } | null
#   }
#
# Notes:
#   - The script is the single deterministic source of Bugsnag context. Skills must
#     never call api.bugsnag.com directly: changes to the JSON shape happen here,
#     in one place — mirroring code-review-github/scripts/load-issue.sh. The shared
#     parse / HTTP / slug-resolution helpers live in _lib.sh alongside this script.
#   - Bugsnag's Data Access API keys resources by numeric id, not by the slugs that
#     appear in dashboard URLs, so the script resolves org slug -> org id ->
#     project slug -> project id before fetching the error.
#   - `linkedIssues` surfaces issues linked to the error (e.g. the mirrored GitHub
#     issue), so downstream skills can route the technical report to the linked PR.
#   - `latestEvent.stacktrace` carries the full frame list (slimmed); `inProject`
#     flags application frames, which is the entry point for a TDD reproduction.
#
# Known limitations (intentionally out of scope, fall back to Bugsnag MCP):
#   - Per-event pivots / custom event fields beyond the latest event
#   - Trend / stability time series
#   - Attachment binary contents
#
# Exit codes:
#   1  usage error (missing or unparseable argument)
#   2  missing required tool (curl, jq) or missing BUGSNAG_TOKEN
#   3  Bugsnag fetch failed (auth, not found, or API error)
set -euo pipefail

PROG="${0##*/}"
# shellcheck source=_lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/_lib.sh"

usage() {
  cat >&2 <<'EOF'
Usage: load-issue.sh <URL|ORG_SLUG/PROJECT_SLUG/ERROR_ID>

  URL     an app.bugsnag.com URL of the form
          https://app.bugsnag.com/<org>/<project>/errors/<error-id>
  TRIPLE  <org-slug>/<project-slug>/<error-id>

Auth: export BUGSNAG_TOKEN with a Data Access API token.
EOF
}

if [[ $# -ne 1 || -z "${1:-}" ]]; then
  usage
  exit 1
fi

bsnag_require_tools
bsnag_require_token

parsed="$(bsnag_parse_ref "$1")" || exit 1
ORG_SLUG="$(printf '%s' "$parsed" | awk '{print $1}')"
PROJ_SLUG="$(printf '%s' "$parsed" | awk '{print $2}')"
ERROR_ID="$(printf '%s' "$parsed" | awk '{print $3}')"

# --- resolve org id + project id (slugs are not API keys) -------------------
ORGS_JSON="$(bsnag_get "${API}/user/organizations")"
ORG_ID="$(printf '%s' "$ORGS_JSON" | jq -r --arg s "$ORG_SLUG" 'map(select(.slug == $s)) | .[0].id // empty')"
if [[ -z "$ORG_ID" ]]; then
  echo "${PROG}: organization slug not found or not accessible: $ORG_SLUG" >&2
  exit 3
fi
PROJ_JSON="$(bsnag_resolve_project_json "$ORG_ID" "$PROJ_SLUG")"
PROJ_ID="$(printf '%s' "$PROJ_JSON" | jq -r '.id')"

# --- fetch error, comments, latest event -----------------------------------
ERROR_JSON="$(bsnag_get "${API}/projects/${PROJ_ID}/errors/${ERROR_ID}")"
COMMENTS_JSON="$(bsnag_get "${API}/projects/${PROJ_ID}/errors/${ERROR_ID}/comments")"

# latest_event may legitimately 404 (event pruned); degrade to null rather than abort.
EVENT_JSON='null'
if ev="$(curl -sS -w $'\n%{http_code}' \
      -H "Authorization: token ${TOKEN}" -H "X-Version: 2" \
      "${API}/projects/${PROJ_ID}/errors/${ERROR_ID}/latest_event" 2>/dev/null)"; then
  ev_http="${ev##*$'\n'}"
  ev_body="${ev%$'\n'*}"
  if [[ "$ev_http" -ge 200 && "$ev_http" -lt 300 ]]; then
    EVENT_JSON="$ev_body"
  fi
fi

DASH_URL="https://app.bugsnag.com/${ORG_SLUG}/${PROJ_SLUG}/errors/${ERROR_ID}"

# --- assemble stable JSON ---------------------------------------------------
jq -n \
  --arg dashUrl "$DASH_URL" \
  --arg orgSlug "$ORG_SLUG" \
  --arg orgId "$ORG_ID" \
  --argjson org "$ORGS_JSON" \
  --argjson project "$PROJ_JSON" \
  --argjson error "$ERROR_JSON" \
  --argjson comments "$COMMENTS_JSON" \
  --argjson event "$EVENT_JSON" '
def map_comments:
  [ (. // [])[] | {
      author:    (.collaborator.name // null),
      email:     (.collaborator.email // null),
      body:      (.message // ""),
      createdAt: (.created_at // null),
      updatedAt: (.updated_at // null)
  } ];

def map_linked:
  [ (. // [])[] | {
      type:   (.type // null),
      number: (.number // null),
      url:    (.url // null)
  } ];

def map_event:
  if . == null then null else
  (.exceptions[0] // {}) as $exc
  | {
      id:         (.id // null),
      receivedAt: (.received_at // null),
      context:    (.context // null),
      unhandled:  (.unhandled // null),
      severity:   (.severity // null),
      errorClass: ($exc.error_class // null),
      message:    ($exc.message // null),
      app: {
        id:           (.app.id // null),
        version:      (.app.version // null),
        releaseStage: (.app.release_stage // null),
        type:         (.app.type // null)
      },
      device: {
        osName:          (.device.os_name // null),
        osVersion:       (.device.os_version // null),
        runtimeVersions: (.device.runtime_versions // null)
      },
      request: {
        httpMethod: (.request.http_method // null),
        url:        (.request.url // null),
        clientIp:   (.request.client_ip // null)
      },
      user: {
        id:    (.user.id // null),
        name:  (.user.name // null),
        email: (.user.email // null)
      },
      stacktrace: [ ($exc.stacktrace // [])[] | {
        file:      (.file // null),
        line:      (.line_number // null),
        method:    (.method // null),
        inProject: (.in_project // false)
      } ],
      breadcrumbs: [ (.breadcrumbs // [])[] | {
        timestamp: (.timestamp // null),
        name:      (.name // null),
        type:      (.type // null)
      } ]
  } end;

$error as $e
| {
    kind: "bugsnag-error",
    id: ($e.id // null),
    url: $dashUrl,
    apiUrl: ($e.url // null),
    organization: {
      id: $orgId,
      slug: $orgSlug,
      name: ($org | map(select(.slug == $orgSlug)) | .[0].name // null)
    },
    project: {
      id: ($project.id // null),
      slug: ($project.slug // null),
      name: ($project.name // null),
      type: ($project.type // null),
      language: ($project.language // null)
    },
    title: ($e.error_class // null),
    errorClass: ($e.error_class // null),
    message: ($e.message // ""),
    context: ($e.context // null),
    status: ($e.status // null),
    severity: ($e.severity // null),
    events: ($e.events // 0),
    users: ($e.users // 0),
    firstSeen: ($e.first_seen // null),
    lastSeen: ($e.last_seen // null),
    createdAt: ($e.first_seen // null),
    assignedCollaboratorId: ($e.assigned_collaborator_id // null),
    assignedTeamId: ($e.assigned_team_id // null),
    releaseStages: ($e.release_stages // []),
    linkedIssues: ($e.linked_issues | map_linked),
    commentCount: ($e.comment_count // ($comments | length)),
    comments: ($comments | map_comments),
    groupingFields: {
      errorClass: ($e.grouping_fields.errorClass // null),
      file: ($e.grouping_fields.file // null)
    },
    latestEvent: ($event | map_event)
  }
'
