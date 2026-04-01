---
name: create-tracker-issue-from-cr-comments
description: Use when create a tracker-ready issue draft from technical code
  review comments while preserving original task text and making the result
  understandable for both AI agents and non-technical stakeholders.
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Create Tracker Issue From CR Comments

**Constraint:**
- For all GitHub operations, prefer GitHub CLI (`gh`) as the primary tool.
- If `gh` is not available or cannot be used, use an available GitHub MCP server as fallback.
- If neither `gh` nor a GitHub MCP server is available, stop and return a failed result explaining that required GitHub tools are missing.
- First, load all rules for the cursor editor (`.cursor/rules/.*mdc`) and read `project.mdc`.
- Output must be in the language in which the assignment was written.
- Never use a web browser for issue and PR analysis when CLI/MCP tools are available.
- Keep the original assignment text unchanged; only improve formatting and structure.

**Steps:**
- Open the provided issue/PR/CR URL and collect:
  - the original assignment text,
  - technical CR comments and review thread context,
  - any linked implementation constraints.
- Analyze repository context before drafting output:
  - if a related PR exists, load and inspect it,
  - otherwise inspect the default branch in git.
- Build a task list from CR comments and assignment context:
  - include unresolved requirements only,
  - remove already-resolved or duplicate requests.
- Prepare a tracker-ready markdown issue draft:
  - preserve original assignment text exactly (verbatim section),
  - add a clear goal summary understandable for non-technical readers,
  - add a technical section for AI agents and developers,
  - keep acceptance criteria concrete and testable.
- If attachments are referenced, download and analyze them via CLI/MCP and include their impact in the draft.

## Output format (markdown)

```markdown
## Cíl
<Stručné, srozumitelné shrnutí cíle pro netechnické publikum>

## Původní zadání (beze změn)
<Přesně původní text zadání, bez úprav obsahu>

## Technický kontext z CR
- <Shrnutí relevantních technických zjištění>

## Požadavky pro implementaci
- [ ] <Konkrétní požadavek 1>
- [ ] <Konkrétní požadavek 2>

## Akceptační kritéria
- [ ] <Měřitelné kritérium 1>
- [ ] <Měřitelné kritérium 2>

## Poznámky
- Zdroj: <HTTP odkaz na issue/PR/CR>
- Výstup je naformátovaný pro issue tracker, původní zadání zůstalo obsahově beze změn.
```

## Quality checks
- Verify that original assignment wording in the verbatim section is identical to the source.
- Verify that all unresolved CR comments are mapped to implementation requirements.
- Verify that acceptance criteria are testable and unambiguous.

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
