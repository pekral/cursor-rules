# Plán: Bash scripty pro AI agenta nad JIRA

Stav: **implementováno a ověřeno** proti SS-3046 (build 212 testů zelený). Vzniklo ze
`/analyze-problem`, rozsah pak rozšířen uživatelem o komplexní context-loader.

Reálně dodáno (oproti původnímu návrhu níže přibyl `gather-issue-context.sh`):

| Operace | Script | Stav |
|---|---|---|
| Čtení issue | `load-issue.sh` (existoval) | beze změny |
| Vkládání/úprava komentáře | `upsert-comment.sh` (existoval) | beze změny |
| Parsování komentářů | `parse-comments.sh` (nový) | hotovo |
| Komplexní kontext (issue + komentáře + přílohy + rekurzivně propojené issues + inventář URL → Markdown brief) | `gather-issue-context.sh` (nový) | hotovo |
| Změna statusu → Code Review | `transition-to-code-review.sh` (nový) | hotovo |

Wiring do skillů: `rules/jira/general.mdc` (výjimka pro transition), `code-review-jira`
(katalog scriptů), `resolve-issue` (gather + transition po PR), `prepare-issue-context`
(gather), `tester-cookbook` (gather).

---

Původní návrh (pro kontext):

## Goal

AI agent má mít k dispozici sadu deterministických bash scriptů pro čtyři operace nad
JIRA: (1) čtení issue, (2) vkládání/úpravu komentáře, (3) parsování existujících
komentářů do strukturované podoby, (4) jedinou povolenou změnu statusu — přechod na stav
"Code Review". Po dokončení agent zvládne všechny čtyři operace jediným voláním scriptu,
bez ad-hoc `acli` příkazů a bez rizika, že posune issue do jiného než review stavu.

## Architecture

Vše žije v **existujícím** domově JIRA nástrojů `skills/code-review-jira/scripts/`, vedle
`load-issue.sh` a `upsert-comment.sh`. Nezavádíme nový adresář ani novou abstrakci —
stavíme na tom, co už repo má (`acli` jako primární nástroj, JIRA MCP jako fallback,
viz `rules/jira/general.mdc`).

Mapování operace → script:

| Operace | Řešení | Nový kód? |
|---|---|---|
| Čtení issue | `load-issue.sh <KEY\|URL>` (už existuje) | ne |
| Vkládání/úprava komentáře | `upsert-comment.sh <KEY\|URL> <BODY\|-> [MARKER]` (už existuje) | ne |
| Parsování komentářů | `parse-comments.sh <KEY\|URL>` (nový, tenká vrstva nad `load-issue.sh`) | ano |
| Změna statusu → Code Review | `transition-to-code-review.sh <KEY\|URL> [STATUS]` (nový) | ano |

Proč tady a ne nová abstrakce: parsování komentářů je čistá projekce výstupu
`load-issue.sh`, transition je tenký a bezpečnostně omezený wrapper nad
`acli jira workitem transition`. Oba sdílí normalizaci KEY/URL, kterou už dělají
stávající scripty — viz "Known debt" níže.

## Implementation steps

1. **Úprava pravidla `rules/jira/general.mdc` (governance, vyžaduje souhlas člověka).**
   Řádek 9 dnes říká "Never change JIRA issue status." Nahradit zněním s jedinou výjimkou:
   > Never change JIRA issue status, with one exception: a single allowed transition to the
   > project's Code Review status, performed only via
   > `skills/code-review-jira/scripts/transition-to-code-review.sh`. Every other transition
   > (Done, In Progress, Closed, …) stays human-only.
   Toto je změna **sdíleného** pravidla — dopadá na všechny skills, které ho importují
   (`resolve-issue`, `tester-cookbook`, `code-review-jira`, `process-code-review`). Proto
   krok 1 a s explicitním souhlasem.

2. **`parse-comments.sh <KEY|URL>`** — extrakce komentářů.
   - Zavolá `load-issue.sh "$1"` a přes `jq` vyprojektuje `.comments[]` na pole objektů:
     `{ index, author, created, visibility, body, charCount, lineCount }`.
   - `index` je 0-based pořadí. `charCount`/`lineCount` slouží agentovi k rozhodnutí, jestli
     komentář číst celý nebo po částech.
   - Výstup: jedno JSON pole na stdout (deterministické, slurpovatelné `jq`). Žádný `--text`
     mód — formátování pro člověka si udělá agent (YAGNI; přidá se, až bude reálný caller).
   - Propaguje exit kódy z `load-issue.sh` (2 = chybí nástroj, 3 = fetch selhal).

3. **`transition-to-code-review.sh <KEY|URL> [STATUS]`** — jediný povolený přechod.
   - Normalizuje KEY z arg (stejně jako stávající scripty).
   - Cílový stav: `STATUS` arg → jinak env `JIRA_CODE_REVIEW_STATUS` → jinak default
     `"Code Review"`.
   - **Whitelist guard:** cílový stav musí (case-insensitive) odpovídat seznamu synonym
     review stavu (`JIRA_CODE_REVIEW_SYNONYMS`, default
     `Code Review,In Review,Review,Ready for Review,CR`). Cokoli mimo seznam → exit 1
     ("refused: only the Code Review transition is allowed"). Script tak **strukturálně
     nemůže** posunout issue na Done apod.
   - Idempotence: přes `load-issue.sh` přečte aktuální `status`; pokud už je v cílovém stavu,
     no-op a exit 0.
   - Provede `acli jira workitem transition --key "$KEY" --status "$TARGET" --yes --json`.
   - **Discovery / doptání (požadavek uživatele):** `acli` neumí vypsat dostupné přechody
     (viz `load-issue.sh` Known limitations). Když transition selže proto, že cílový stav
     v daném projektu neexistuje / není dostupný z aktuálního stavu, script skončí
     vyhrazeným exit kódem 5 s instrukcí: agent zjistí skutečný název review stavu přes
     JIRA MCP (available next transitions), ověří ho proti whitelistu a spustí script znovu
     se správným `STATUS`; pokud nelze jednoznačně určit, **zeptá se člověka**. Ostatní
     selhání API → exit 3, chybějící `acli` → exit 2.

4. **Dokumentace v `skills/code-review-jira/SKILL.md`** — doplnit do sekce o scriptech
   krátký odstavec se vzory volání všech čtyř operací (vč. dvou stávajících), aby je agent
   našel na jednom místě.

5. **Build gate.** Před pushnutím spustit `composer build` (a `composer skill-check`),
   opravit vše. Viz CLAUDE.md.

### Known debt (zapsat do compound-memory projektu)

Normalizace KEY/URL je teď duplikovaná ve `load-issue.sh` i `upsert-comment.sh` a přibude
ve dvou nových scriptech (4×). Až bude třeba pátá kopie, vytáhnout sdílený
`scripts/lib-jira-key.sh`. Teď ne — refaktor stávajícího kódu je mimo zadání (CLAUDE.md §3).

## Sources

- `skills/code-review-jira/scripts/load-issue.sh` — čtení issue + komentářů (`acli jira workitem view --json`, `comment list --json --paginate`), stabilní JSON shape vč. `.comments[]`.
- `skills/code-review-jira/scripts/upsert-comment.sh` — idempotentní comment upsert (anchor podle aktora).
- `rules/jira/general.mdc:9` — zákaz změny statusu (mění krok 1); řádky 16–31 — povinný Wiki Markup pro komentáře.
- `acli jira workitem transition --help` — `--key`, `--status "<name>"`, `--yes`, `--json`; přechod podle **jména** cílového stavu.
- `acli jira workitem comment list --help` — `--json --paginate`.
- Požadavek uživatele: status smí jít **jen na "Code Review"**, název se liší projekt od projektu → najít nebo se doptat.

## Success criteria

- `parse-comments.sh <KEY>` vrátí validní JSON pole komentářů s poli `index, author, created, visibility, body, charCount, lineCount`; prázdné issue → `[]`.
- `transition-to-code-review.sh <KEY>` posune issue do review stavu a je idempotentní (druhé spuštění = no-op, exit 0).
- `transition-to-code-review.sh <KEY> "Done"` skončí exit 1 a issue nezmění (whitelist guard).
- Při neznámém názvu review stavu skončí exit 5 s instrukcí pro MCP discovery / doptání, nikdy netipuje náhodný přechod.
- Chybějící `acli` → exit 2; selhání API → exit 3 (konzistentní se stávajícími scripty).
- `composer build` a `composer skill-check` prochází.
- `rules/jira/general.mdc` obsahuje výjimku omezenou na jediný script a jediný cílový stav.
