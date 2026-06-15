# Plán: první-třídní integrace Bugsnagu (parita s GitHub a JIRA skilly)

## Goal

Povýšit Bugsnag z dnešního „pass-through na zrcadlený GitHub issue" na **plnohodnotný tracker**, který umí
všechno, co dnes umí GitHub a JIRA: načíst error + komentáře + kontext (události, stacktrace, metadata),
analyzovat a vyřešit problém přes `resolve-issue`, a publikovat zpět — netechnický report jako komentář
přímo do Bugsnag erroru a (volitelně) označit error jako *Fixed* / přiřadit. Integrace musí být
deterministická a agent-friendly přes CLI nebo MCP, ne ad-hoc voláním REST.

## Architecture

Integrace **kopíruje existující tracker pattern**, nezavádí nový (compound-engineering: sáhnout po
existující části systému). Každý tracker = dvojice bash skriptů (`load-issue.sh` + `upsert-comment.sh`)
s pevným JSON kontraktem, nad nimi wrapper skill `code-review-<tracker>` a sdílené orchestrátory
(`resolve-issue`, `pr-summary`, `assignment-compliance-check`, `prepare-issue-context`).

Domov změny:

- **Nová dvojice skriptů** `skills/code-review-bugsnag/scripts/load-issue.sh` a `.../upsert-comment.sh` —
  přesně podle vzoru `skills/code-review-github/scripts/*` a `skills/code-review-jira/scripts/*`.
- **Backend skriptů:** primárně `bugsnag` CLI (`yoanbernabeu/bugsnag-cli`, JSON default, deterministické
  exit kódy) pro čtení a `comments create`; pro write operace, které CLI neumí (změna stavu erroru, přiřazení),
  a jako no-binary fallback přímý `curl` na `https://api.bugsnag.com` s hlavičkou `Authorization: token $BUGSNAG_TOKEN`.
  MCP server (`tgeselle/bugsnag-mcp` nebo oficiální SmartBear MCP) je dokumentovaný fallback — stejně jako
  GitHub/JIRA MCP dnes.
- **Nový wrapper** `skills/code-review-bugsnag/SKILL.md` (paralela ke `code-review-jira`): CR běží nad
  linkovaným GitHub PR, technický report jde na GitHub PR, netechnický na Bugsnag error.
- **Úpravy sdílených bodů** (4–5 souborů): `resolve-issue/references/source-detection.md` (řádek Bugsnag
  místo pass-through), `resolve-issue/SKILL.md` (sekce posílání reportu), `pr-summary` (nová šablona
  `templates/pr-summary-bugsnag.md` + Bugsnag jako cíl), `prepare-issue-context`, `assignment-compliance-check`,
  `code-review/SKILL.md`. A nové pravidlo `.claude/rules/bugsnag/general.mdc` (paralela k `rules/jira/general.mdc`):
  tooling (`bugsnag` CLI → MCP fallback), formát komentáře, a default **„stav erroru mění jen člověk"**
  shodný s JIRA stance.

Datový mapping Bugsnag → existující kontrakt: Bugsnag **Error** = „issue"; Bugsnag **Events/occurrences** =
stacktrace + breadcrumbs + app/device metadata (vstup pro TDD reprodukci); Bugsnag **Comments** = `comments[]`;
linkovaný GitHub PR = cíl technického CR.

## Implementation steps

1. **Rozhodnout tooling & auth** — potvrdit `bugsnag` CLI jako primární backend, `BUGSNAG_TOKEN` jako env var,
   `https://api.bugsnag.com` jako base URL. Ověřit, že token (personal/data-access, ne notifier API key) má
   scope na čtení errors/events/comments a na write (update error, create comment).
2. **`load-issue.sh`** — vstup: Bugsnag URL nebo error ID; výstup: jeden JSON dokument se stabilním tvarem
   (`kind`, `id`, `url`, `title`, `body`/error class+message, `status`, `severity`, `assignee`, `comments[]`,
   `latestEvent` se stacktrace/breadcrumbs/metadata, `relatedGitHubIssue`/`relatedPullRequest`, `createdAt`,
   `firstSeen`, `lastSeen`). Exit kódy 1=usage, 2=missing tool, 3=API fail. Fallback na MCP při 2/3.
3. **`upsert-comment.sh`** — vstup: error ID/URL + tělo ze stdin + marker; chování: čerstvý komentář per běh
   (model jako GitHub, ne edit-in-place — Bugsnag komentáře nejsou per-actor klíčované). Exit kódy jako GitHub.
4. **`code-review-bugsnag/SKILL.md`** — wrapper podle `code-review-jira`: detekuj linkovaný GitHub PR, spusť
   CR řetězec, technický report na GitHub PR, netechnický přes `pr-summary` na Bugsnag error.
5. **`pr-summary`** — přidat `templates/pr-summary-bugsnag.md` a Bugsnag jako rozpoznaný cíl; publikace přes
   `code-review-bugsnag/scripts/upsert-comment.sh`.
6. **Aktualizovat source-detection** v `resolve-issue/references/source-detection.md`: Bugsnag řádek →
   `Load context via skills/code-review-bugsnag/scripts/load-issue.sh <URL|ID>; fall back to Bugsnag MCP`.
   Sjednotit detekční regex tam, kde se opakuje (`prepare-issue-context`, `assignment-compliance-check`, `pr-summary`).
7. **`rules/bugsnag/general.mdc`** — tooling, formát komentáře, default „status mění jen člověk" (volitelný
   write se zapne explicitně).
8. **`resolve-issue/SKILL.md`** — sekce posílání reportu: Bugsnag → komentář přímo do Bugsnag erroru
   (místo „linked GitHub issue if available"); volitelně po merge označit error *Fixed* přes API.
9. **Tests / ověření** — fixtury JSON výstupu loaderu, ověřit `composer build` čistý, projít celý
   `resolve-issue` flow nad reálným Bugsnag errorem v dry-run.

## Sources

- Existující tracker pattern: `skills/code-review-github/scripts/load-issue.sh`,
  `skills/code-review-github/scripts/upsert-comment.sh`, `skills/code-review-jira/scripts/load-issue.sh`,
  `skills/code-review-jira/scripts/upsert-comment.sh`, `skills/pr-summary/SKILL.md`,
  `skills/pr-summary/templates/pr-summary-{github,jira}.md`.
- Detekce a orchestrace: `skills/resolve-issue/SKILL.md`, `skills/resolve-issue/references/source-detection.md`,
  `skills/prepare-issue-context/SKILL.md`, `skills/assignment-compliance-check/SKILL.md`, `skills/code-review/SKILL.md`.
- JIRA pravidlo jako vzor: `.claude/rules/jira/general.mdc`.
- Bugsnag Data Access API: base `https://api.bugsnag.com`, auth `Authorization: token <TOKEN>`
  (Getting Started — developer.smartbear.com/bugsnag/docs/getting-started). Endpointy: organizations,
  projects, errors (list/get/**update** status fix/open/ignore/snooze + assign), events (list/get s full
  stacktrace), comments (list/**create**), pivots, trends, releases, stability
  (bugsnagapiv2.docs.apiary.io; tshddx/bugsnag-api; bugsnag/bugsnag-api-ruby README).
- CLI: `yoanbernabeu/bugsnag-cli` — agent-friendly, JSON default, read + `comments create`, **bez** write
  stavu erroru. MCP: `tgeselle/bugsnag-mcp` (npx, read/investigate), oficiální SmartBear MCP.

## Success criteria

- `skills/code-review-bugsnag/scripts/load-issue.sh <bugsnag-url>` vrátí validní JSON se stejným tvarem
  kontraktu jako GitHub/JIRA loadery (ověřitelné `jq`), exit 0.
- `resolve-issue` s Bugsnag URL/ID načte error + komentáře + poslední událost se stacktrace, vyřeší přes
  TDD a otevře PR — bez ručního přepisu na GitHub issue.
- Netechnický report se publikuje **přímo do Bugsnag erroru** jako komentář (ne jen na linkovaný GitHub issue).
- `assignment-compliance-check`, `pr-summary`, `prepare-issue-context` rozpoznají Bugsnag jako první-třídní
  zdroj/cíl.
- Žádné nové ad-hoc REST volání mimo dvojici skriptů; auth jen přes `BUGSNAG_TOKEN`; žádný secret v repu.
- `composer build` čistý; coverage 100 % pro nový/změněný kód.
