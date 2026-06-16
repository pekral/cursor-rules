#!/usr/bin/env bash
# gather-issue-context.sh — assemble everything needed to work on a JIRA issue
# into a single agent-readable Markdown context brief.
#
# Builds on load-issue.sh (the JIRA data layer) and adds:
#   - the full issue: fields, description, comments, attachments, custom fields
#   - recursive traversal of related JIRA issues (parent, subtasks, issue links)
#     up to JIRA_CONTEXT_DEPTH, cycle-safe and capped
#   - an inventory of every external URL found in descriptions and comments
#
# The output is Markdown meant to be read by an AI agent as task context, NOT a
# JIRA comment — so it deliberately uses Markdown, not Wiki Markup.
#
# Usage:
#   gather-issue-context.sh <KEY|URL>
#
# Env:
#   JIRA_CONTEXT_DEPTH      how many link hops to follow (default 1, 0 = root only)
#   JIRA_CONTEXT_MAX_ISSUES safety cap on total issues loaded (default 25)
#
# acli limits (handled by deferring to the agent, stated in the output):
#   - acli cannot download attachment content (only list); the brief lists each
#     attachment + its contentUrl so the agent fetches it with its own tools.
#   - external URLs are inventoried, not fetched; the agent follows them
#     (recursively, if useful) via its web tools, honouring the project's
#     outbound-request security rules.
#
# Exit codes:
#   1  usage / argument error
#   2  missing required tool (jq, or a tool load-issue.sh needs)
#   3  JIRA fetch failed for the root issue
set -euo pipefail

usage() {
  cat >&2 <<'EOF'
Usage: gather-issue-context.sh <KEY|URL>

  KEY  JIRA issue key (e.g. ECOMAIL-1234)
  URL  /browse/<KEY> URL or any URL containing ?selectedIssue=<KEY>

Env: JIRA_CONTEXT_DEPTH (default 1), JIRA_CONTEXT_MAX_ISSUES (default 25)
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

DEPTH="${JIRA_CONTEXT_DEPTH:-1}"
MAX_ISSUES="${JIRA_CONTEXT_MAX_ISSUES:-25}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

load() { "$SCRIPT_DIR/load-issue.sh" "$1" 2>/dev/null; }

# Render one issue's JSON as a Markdown section. $2 = "full" | "compact".
render_issue() {
  local json="$1" mode="$2"
  printf '%s' "$json" | jq -r --arg mode "$mode" '
    def line(lbl; v): if (v // "") == "" then empty else "- **\(lbl):** \(v)" end;

    "## \(.key) — \(.summary // "(bez názvu)")",
    "",
    line("Stav"; .status),
    line("Typ"; .issueType),
    line("Priorita"; .priority),
    line("Assignee"; .assignee),
    line("Reporter"; .reporter),
    line("Vytvořeno"; .created),
    line("Aktualizováno"; .updated),
    line("Štítky"; (.labels // [] | join(", "))),
    line("Komponenty"; (.components // [] | join(", "))),
    line("URL"; .url),
    "",
    (if (.descriptionText // "") == "" then "_Bez popisu._"
     else "### Popis\n\n" + .descriptionText end),
    "",
    ( if $mode != "full" then empty else
        ( (.comments // []) as $c
          | "### Komentáře (\($c | length))",
            "",
            ( if ($c | length) == 0 then "_Žádné komentáře._"
              else ( $c | to_entries[]
                     | "#### [\(.key)] \(.value.author // "?") — \(.value.created // "?")"
                       + (if (.value.visibility // "public") != "public" then " _(neveřejný)_" else "" end)
                       + "\n\n" + (.value.body // "") )
              end ) ),
        "",
        ( (.attachments // []) as $a
          | "### Přílohy (\($a | length))",
            "",
            ( if ($a | length) == 0 then "_Žádné přílohy._"
              else ( $a[]
                     | "- **\(.name)** (\(.mimeType // "?"), \(.size // 0) B) — \(.contentUrl // "?")" )
              end ) ),
        "",
        ( (.subtasks // []) as $s
          | if ($s | length) == 0 then empty
            else ( "### Subtasky", "", ($s[] | "- \(.key) — \(.summary // "") [\(.status // "?")]") )
            end ),
        ( (.issueLinks // []) as $l
          | if ($l | length) == 0 then empty
            else ( "", "### Odkazy na issues", "",
                   ($l[] | "- \(.verb // .type // "souvisí") **\(.linkedKey)** — \(.linkedSummary // "") [\(.linkedStatus // "?")]") )
            end ),
        ( (.pullRequests // []) as $p
          | if ($p | length) == 0 then empty
            else ( "", "### Pull requesty", "",
                   ($p[] | "- #\(.number) \(.title // "") [\(.state // "?")] — \(.url // "")") )
            end ),
        ( (.customFields // {}) as $cf
          | ( $cf | to_entries | map(select(.value != null and .value != "" and .value != [] and .value != {})) ) as $filled
          | if ($filled | length) == 0 then empty
            else ( "", "### Vyplněná custom fields", "",
                   ($filled[] | "- **\(.key):** \(.value | if type=="object" or type=="array" then tojson else tostring end)") )
            end )
      end ),
    ""
  '
}

# --- Root issue ---------------------------------------------------------------
ROOT_JSON="$(load "$1")" || true
if [[ -z "$ROOT_JSON" ]] || ! printf '%s' "$ROOT_JSON" | jq -e '.key' >/dev/null 2>&1; then
  echo "gather-issue-context.sh: failed to load root issue: $1" >&2
  exit 3
fi
ROOT_KEY="$(printf '%s' "$ROOT_JSON" | jq -r '.key')"
ROOT_SUMMARY="$(printf '%s' "$ROOT_JSON" | jq -r '.summary // ""')"

# --- BFS over related issues --------------------------------------------------
declare -a SEEN=("$ROOT_KEY")
seen() { local k; for k in "${SEEN[@]}"; do [[ "$k" == "$1" ]] && return 0; done; return 1; }

related_keys() {
  printf '%s' "$1" | jq -r '
    [ (.parent.key // empty),
      (.subtasks // [] | .[].key),
      (.issueLinks // [] | .[].linkedKey) ] | .[] // empty'
}

# Collect related JSON breadth-first up to DEPTH, capped at MAX_ISSUES.
RELATED_JSON_FILE="$(mktemp)"
trap 'rm -f "$RELATED_JSON_FILE"' EXIT
declare -a FRONTIER
while IFS= read -r k; do [[ -n "$k" ]] && FRONTIER+=("$k"); done < <(related_keys "$ROOT_JSON")

depth=1
while [[ "$depth" -le "$DEPTH" && "${#FRONTIER[@]}" -gt 0 ]]; do
  declare -a NEXT=()
  for k in "${FRONTIER[@]}"; do
    seen "$k" && continue
    [[ "${#SEEN[@]}" -ge "$MAX_ISSUES" ]] && break
    SEEN+=("$k")
    j="$(load "$k")" || true
    [[ -z "$j" ]] && continue
    printf '%s\n' "$j" >> "$RELATED_JSON_FILE"
    while IFS= read -r nk; do [[ -n "$nk" ]] && NEXT+=("$nk"); done < <(related_keys "$j")
  done
  FRONTIER=("${NEXT[@]:-}")
  depth=$((depth + 1))
done

# --- URL inventory (root + related) ------------------------------------------
url_text() { printf '%s' "$1" | jq -r '[ (.descriptionText // ""), ((.comments // [])[].body // "") ] | join("\n")'; }
URL_INVENTORY="$(
  { url_text "$ROOT_JSON"; [[ -s "$RELATED_JSON_FILE" ]] && while IFS= read -r line; do url_text "$line"; done < "$RELATED_JSON_FILE"; } \
    | grep -oE 'https?://[^][:space:]"`<>(){}]+' \
    | sed -E 's/[].,;:?!`)}>"'"'"']+$//' \
    | grep -E '^https?://[^/]+\.[^/]' \
    | sort -u || true
)"

# --- Emit Markdown brief ------------------------------------------------------
echo "# Kontext JIRA: ${ROOT_KEY} — ${ROOT_SUMMARY}"
echo
echo "> Vygenerováno \`gather-issue-context.sh\` jako kontext pro práci na úkolu (depth=${DEPTH}, max=${MAX_ISSUES}). Načteno issues: ${#SEEN[@]}."
echo
render_issue "$ROOT_JSON" full

if [[ -s "$RELATED_JSON_FILE" ]]; then
  echo "# Propojené issues"
  echo
  while IFS= read -r line; do
    [[ -n "$line" ]] && render_issue "$line" compact
  done < "$RELATED_JSON_FILE"
fi

echo "# Inventář odkazů"
echo
if [[ -n "$URL_INVENTORY" ]]; then
  echo "Externí odkazy nalezené v popisech a komentářích. Obsah načte agent svými web nástroji (rekurzivně dle potřeby), v souladu s bezpečnostními pravidly pro outbound requesty:"
  echo
  printf '%s\n' "$URL_INVENTORY" | sed 's/^/- /'
else
  echo "_Žádné externí odkazy nenalezeny._"
fi
echo
echo "---"
echo "## Pokyny pro agenta"
echo "- **Obsah příloh** není v tomto dokumentu — \`acli\` přílohy nestahuje. Načti je sám přes \`contentUrl\` (autentizovaně) vlastními nástroji."
echo "- **Externí odkazy** výše projdi vlastními web nástroji; pokud vedou na další relevantní zdroje, pokračuj rekurzivně do rozumné hloubky."
echo "- Propojené JIRA issues jsou načtené do hloubky ${DEPTH}; pro hlubší kontext zvyš \`JIRA_CONTEXT_DEPTH\`."
