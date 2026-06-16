#!/usr/bin/env bash
# gather-issue-context.sh — assemble everything needed to work on a GitHub
# issue or PR into a single agent-readable Markdown context brief.
#
# Builds on load-issue.sh (the GitHub data layer) and adds:
#   - the full issue/PR: header, body, comments, labels, and (for PRs) the diff
#     stat, files, commits, reviews, and CI status checks
#   - recursive traversal of linked issues/PRs (closingIssues for a PR,
#     closingPullRequests for an issue) up to GITHUB_CONTEXT_DEPTH, cycle-safe
#     and capped
#   - an inventory of every external URL found in the body and comments
#
# The output is Markdown meant to be read by an AI agent as task context, not a
# PR comment.
#
# Usage:
#   gather-issue-context.sh <NUMBER|URL>
#
# Env:
#   GITHUB_CONTEXT_DEPTH      how many link hops to follow (default 1, 0 = root only)
#   GITHUB_CONTEXT_MAX_ITEMS  safety cap on total issues/PRs loaded (default 25)
#
# load-issue.sh limits (handled by deferring to the agent, stated in the output):
#   - attachment / image binary content is not fetched (only URLs are returned);
#     the agent reads it with its own tools.
#   - external URLs are inventoried, not fetched; the agent follows them
#     (recursively, if useful) via its web tools, honouring the project's
#     outbound-request security rules.
#
# Exit codes:
#   1  usage / argument error
#   2  missing required tool (jq, or a tool load-issue.sh needs)
#   3  GitHub fetch failed for the root issue/PR
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: gather-issue-context.sh <NUMBER|URL>

  NUMBER  bare GitHub issue or PR number (e.g. 445)
  URL     any github.com URL containing /issues/<N> or /pull/<N>

Env: GITHUB_CONTEXT_DEPTH (default 1), GITHUB_CONTEXT_MAX_ITEMS (default 25)
EOF
}

if [[ $# -ne 1 || -z "${1:-}" ]]; then
  usage
  exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
  echo "gather-issue-context.sh: required tool not found: jq" >&2
  exit 2
fi

DEPTH="${GITHUB_CONTEXT_DEPTH:-1}"
MAX_ITEMS="${GITHUB_CONTEXT_MAX_ITEMS:-25}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

load() { "$SCRIPT_DIR/load-issue.sh" "$1" 2>/dev/null; }

# Render one issue/PR JSON as a Markdown section. $2 = "full" | "compact".
render_item() {
  local json="$1" mode="$2"
  printf '%s' "$json" | jq -r --arg mode "$mode" '
    def line(lbl; v): if (v // "") == "" then empty else "- **\(lbl):** \(v)" end;

    "## \(.kind | ascii_upcase) #\(.number) — \(.title // "(no title)")",
    "",
    line("State"; .state),
    line("Author"; .author),
    line("Labels"; (.labels // [] | join(", "))),
    line("Milestone"; .milestone),
    line("Created"; .createdAt),
    line("Updated"; .updatedAt),
    line("URL"; .url),
    ( if .kind == "pr" then
        ( line("Branch"; "\(.headRefName // "?") → \(.baseRefName // "?")"),
          line("Draft"; (.isDraft | tostring)),
          line("Mergeable"; "\(.mergeable // "?") / \(.mergeStateStatus // "?")"),
          line("Review decision"; .reviewDecision),
          line("Diff"; "+\(.additions // 0) -\(.deletions // 0) across \(.changedFiles // 0) file(s)") )
      else empty end ),
    "",
    (if (.body // "") == "" then "_No description._" else "### Description\n\n" + .body end),
    "",
    ( if $mode != "full" then empty else
        ( (.comments // []) as $c
          | "### Comments (\($c | length))",
            "",
            ( if ($c | length) == 0 then "_No comments._"
              else ( $c | to_entries[]
                     | "#### [\(.key)] \(.value.author // "?") — \(.value.createdAt // "?")"
                       + "\n\n" + (.value.body // "") )
              end ) ),
        ( (.files // []) as $f
          | if ($f | length) == 0 then empty
            else ( "", "### Changed files (\($f | length))", "",
                   ($f[] | "- `\(.path)` (+\(.additions // 0) -\(.deletions // 0))") )
            end ),
        ( (.commits // []) as $cm
          | if ($cm | length) == 0 then empty
            else ( "", "### Commits (\($cm | length))", "",
                   ($cm[] | "- `\(.oid[0:9])` \(.messageHeadline // "")") )
            end ),
        ( (.reviews // []) as $r
          | if ($r | length) == 0 then empty
            else ( "", "### Reviews", "",
                   ($r[] | "- \(.author // "?") — **\(.state // "?")** (\(.submittedAt // "?"))") )
            end ),
        ( (.statusCheckRollup // []) as $s
          | if ($s | length) == 0 then empty
            else ( "", "### CI status checks", "",
                   ($s[] | "- \(.context // "?") — \(.state // "?")") )
            end ),
        ( (.closingIssues // []) as $ci
          | if ($ci | length) == 0 then empty
            else ( "", "### Closes issues", "",
                   ($ci[] | "- #\(.number) \(.title // "") [\(.state // "?")] — \(.url // "")") )
            end ),
        ( (.closingPullRequests // []) as $cp
          | if ($cp | length) == 0 then empty
            else ( "", "### Closed by pull requests", "",
                   ($cp[] | "- #\(.number) \(.title // "") [\(.state // "?")] — \(.url // "")") )
            end )
      end ),
    ""
  '
}

# --- Root issue/PR ------------------------------------------------------------
ROOT_JSON="$(load "$1")" || true
if [[ -z "$ROOT_JSON" ]] || ! printf '%s' "$ROOT_JSON" | jq -e '.number' >/dev/null 2>&1; then
  echo "gather-issue-context.sh: failed to load root issue/PR: $1" >&2
  exit 3
fi
ROOT_KIND="$(printf '%s' "$ROOT_JSON" | jq -r '.kind')"
ROOT_NUMBER="$(printf '%s' "$ROOT_JSON" | jq -r '.number')"
ROOT_TITLE="$(printf '%s' "$ROOT_JSON" | jq -r '.title // ""')"
ROOT_URL="$(printf '%s' "$ROOT_JSON" | jq -r '.url // ""')"

# --- BFS over linked issues/PRs ----------------------------------------------
declare -a SEEN=("$ROOT_URL")
seen() { local u; for u in "${SEEN[@]}"; do [[ "$u" == "$1" ]] && return 0; done; return 1; }

related_urls() {
  printf '%s' "$1" | jq -r '
    [ (.closingIssues // [] | .[].url),
      (.closingPullRequests // [] | .[].url) ] | .[] // empty'
}

RELATED_JSON_FILE="$(mktemp)"
trap 'rm -f "$RELATED_JSON_FILE"' EXIT
declare -a FRONTIER
while IFS= read -r u; do [[ -n "$u" ]] && FRONTIER+=("$u"); done < <(related_urls "$ROOT_JSON")

depth=1
while [[ "$depth" -le "$DEPTH" && "${#FRONTIER[@]}" -gt 0 ]]; do
  declare -a NEXT=()
  for u in "${FRONTIER[@]}"; do
    seen "$u" && continue
    [[ "${#SEEN[@]}" -ge "$MAX_ITEMS" ]] && break
    SEEN+=("$u")
    j="$(load "$u")" || true
    [[ -z "$j" ]] && continue
    # One compact JSON object per line so the line-based readback below holds.
    printf '%s' "$j" | jq -c . >> "$RELATED_JSON_FILE" 2>/dev/null || continue
    while IFS= read -r nu; do [[ -n "$nu" ]] && NEXT+=("$nu"); done < <(related_urls "$j")
  done
  FRONTIER=()
  [[ ${#NEXT[@]} -gt 0 ]] && FRONTIER=("${NEXT[@]}")
  depth=$((depth + 1))
done

# --- URL inventory (root + related) ------------------------------------------
url_text() { printf '%s' "$1" | jq -r '[ (.body // ""), ((.comments // [])[].body // ""), ((.reviews // [])[].body // "") ] | join("\n")'; }
URL_INVENTORY="$(
  { url_text "$ROOT_JSON"; [[ -s "$RELATED_JSON_FILE" ]] && while IFS= read -r line; do url_text "$line"; done < "$RELATED_JSON_FILE"; } \
    | grep -oE 'https?://[^][:space:]"`<>(){}]+' \
    | sed -E 's/[].,;:?!`)}>"'"'"']+$//' \
    | grep -E '^https?://[^/]+\.[^/]' \
    | sort -u || true
)"

# --- Emit Markdown brief ------------------------------------------------------
echo "# GitHub context: ${ROOT_KIND} #${ROOT_NUMBER} — ${ROOT_TITLE}"
echo
echo "> Generated by \`gather-issue-context.sh\` as task context (depth=${DEPTH}, max=${MAX_ITEMS}). Items loaded: ${#SEEN[@]}."
echo
render_item "$ROOT_JSON" full

if [[ -s "$RELATED_JSON_FILE" ]]; then
  echo "# Linked issues / pull requests"
  echo
  while IFS= read -r line; do
    [[ -n "$line" ]] && render_item "$line" compact
  done < "$RELATED_JSON_FILE"
fi

echo "# URL inventory"
echo
if [[ -n "$URL_INVENTORY" ]]; then
  echo "External links found in the body and comments. The agent fetches their content with its own web tools (recursively when useful), honouring the project's outbound-request security rules:"
  echo
  printf '%s\n' "$URL_INVENTORY" | sed 's/^/- /'
else
  echo "_No external links found._"
fi
echo
echo "---"
echo "## Notes for the agent"
echo "- **Attachment / image content** is not in this document — \`load-issue.sh\` returns only URLs. Fetch it with your own tools."
echo "- **External links** above: follow them with your own web tools; if they lead to further relevant sources, continue recursively to a sensible depth."
echo "- Linked issues / PRs are loaded to depth ${DEPTH}; raise \`GITHUB_CONTEXT_DEPTH\` for deeper context."
