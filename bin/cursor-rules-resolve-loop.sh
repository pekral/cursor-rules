#!/usr/bin/env bash
# cursor-rules-resolve-loop.sh — interaktivní Claude Code loop, který systematicky řeší
# GitHub issues V PROJEKTU, ze kterého ho spustíš (přes vendor/bin), jedno issue
# za průchod. Bez worktrees. Auto mode zapnutý — vidíš celý průběh,
# Esc přeruší krok.
#
# Paralelní spuštění tohoto launcheru je nově serializované daidalosovým
# write-lockem (.claude/run/.daidalos-write.lock): souběžné zápisové běhy
# (full-delivery → talos) nad sdíleným working tree běží jeden po druhém,
# read-only běhy (analysis-only → metis) paralelně. Vlastní worktree =
# vlastní toplevel = vlastní zámek = paralelně. Skript sám se nemění.
#
# Spuštění z projektu (po `composer require pekral/cursor-rules --dev`):
#   vendor/bin/cursor-rules-resolve-loop.sh ["extra text k promptu" ...]
#
# Poziční argumenty: libovolný text, který se PŘIPOJÍ k promptu loopu
#   (např. zúžení záběru: "zaměř se jen na issues typu bug").
#
# Proměnné prostředí:
#   PROJECT=<cesta>       # adresář projektu (default: aktuální adresář)
#   LABEL=Resolve_by_AI   # bere jen issues s tímto labelem (prázdné = jakékoliv). Default: Resolve_by_AI
#   MODE=pr|merge         # pr (default): resolve-issue, zastaví na PR (merge si uděláš sám/sama)
#                         # merge:        autoresolve-oldest-github-issue (celý pipeline VČETNĚ merge)
#   DRY_RUN=1             # nic nespustí — jen vypíše vyhodnocený prompt a příkaz (ověření)
#
# Auto mode = --permission-mode auto: status line "auto mode on (shift+tab to
# cycle) · esc to interrupt". Auto-schvalování řídí rizikový klasifikátor —
# bezpečné akce (edity, read-only příkazy, push na tuhle větev) běží bez dotazu,
# rizikové (force push, push na main, destruktivní mazání, prod deploy) se ptají.
set -euo pipefail

PROJECT="${PROJECT:-$PWD}"
LABEL="${LABEL-Resolve_by_AI}"
MODE="${MODE:-pr}"
EXTRA="$*"   # všechny poziční argumenty = text připojený k promptu

cd "$PROJECT" 2>/dev/null || { echo "✗ Projekt neexistuje: $PROJECT"; exit 1; }

# --- kontroly prostředí -------------------------------------------------------
git rev-parse --is-inside-work-tree >/dev/null 2>&1 \
  || { echo "✗ Není to git repozitář: $PROJECT"; exit 1; }
git remote get-url origin 2>/dev/null | grep -qiE 'github\.com' \
  || { echo "✗ origin remote není GitHub — skilly umí jen GitHub."; exit 1; }
command -v gh >/dev/null 2>&1 || { echo "✗ chybí GitHub CLI (gh)."; exit 1; }
gh auth status >/dev/null 2>&1 || { echo "✗ gh nepřihlášené — spusť: gh auth login"; exit 1; }
command -v claude >/dev/null 2>&1 || { echo "✗ chybí claude CLI."; exit 1; }

# --- skilly přítomné? (projektové .claude/skills nebo globální ~/.claude/skills)
if [ ! -d ".claude/skills/autoresolve-oldest-github-issue" ] \
   && [ ! -d "$HOME/.claude/skills/autoresolve-oldest-github-issue" ]; then
  echo "✗ Skilly cursor-rules nenalezeny."
  echo "  V projektu: vendor/bin/cursor-rules install --editor=claude"
  echo "  Nebo globálně (jednou): cp -r <cursor-rules>/skills/* ~/.claude/skills/"
  exit 1
fi

# --- prompt pro /loop ---------------------------------------------------------
if [ "$MODE" = "merge" ]; then
  PROMPT="/loop autoresolve-oldest-github-issue${LABEL:+ (label ${LABEL})}"
else
  LBL=""; [ -n "$LABEL" ] && LBL=" s labelem ${LABEL}"
  PROMPT="/loop použij agenta daidalos, ať vyřeší nejstarší otevřené issue${LBL} v tomhle repu (zastav se na PR, NEMERGUJ), pak skonči. Když žádné eligible issue není, skonči s hláškou NO_ISSUES."
fi
[ -n "$EXTRA" ] && PROMPT="$PROMPT $EXTRA"

PERM=(--permission-mode auto)

echo "▶ projekt : $PROJECT"
echo "▶ režim   : MODE=$MODE  auto mode (edity bez dotazu)  LABEL=${LABEL:-<any>}"
[ -n "$EXTRA" ] && echo "▶ extra   : $EXTRA"

if [ -n "${DRY_RUN:-}" ]; then
  echo "▶ DRY_RUN — nic se nespouští. Příkaz, který by běžel:"
  echo "  claude ${PERM[*]} \"$PROMPT\""
  exit 0
fi

echo "▶ Claude se spustí interaktivně — Esc přeruší krok, Ctrl+C ukončí loop."
echo
exec claude "${PERM[@]}" "$PROMPT"
