---
name: create-jira-issue-from-pr
description: Use when preparing a JIRA issue draft from GitHub pull request
  context while preserving the original assignment text and making the output
  understandable for both AI agents and non-technical stakeholders.
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Create JIRA Issue From PR

**Constraint:**
- Apply @rules/skills/base-constraints.mdc
- Apply @rules/skills/github-operations.mdc
- Apply @rules/skills/jira-operations.mdc
- Never use a web browser for issue and PR analysis when CLI/MCP tools are available.
- Keep the original assignment text unchanged; only improve formatting and structure.

**Steps:**
- Open the provided GitHub PR URL and collect:
  - original assignment text,
  - technical review comments and review thread context,
  - linked constraints and unresolved findings.
- Analyze repository context before drafting output:
  - inspect the PR diff and related commits,
  - include relevant implementation context needed for delivery.
- Build a task list from PR comments and assignment context:
  - include unresolved requirements only,
  - remove already-resolved or duplicate requests.
- Prepare a JIRA-ready markdown issue draft:
  - preserve original assignment text exactly (verbatim section),
  - add a clear goal summary understandable for non-technical readers,
  - add a technical section for AI agents and developers,
  - keep acceptance criteria concrete and testable.
- If attachments are referenced, download and analyze them via CLI/MCP and include their impact in the draft.
- Create the resulting issue in JIRA (if user asks for creation), assign it to the current user, and return the direct issue URL.

## Output format (markdown)

```markdown
## Cíl
<Stručné, srozumitelné shrnutí cíle pro netechnické publikum>

## Původní zadání (beze změn)
<Přesně původní text zadání, bez úprav obsahu>

## Technický kontext z PR
- <Shrnutí relevantních technických zjištění>

## Požadavky pro implementaci
- [ ] <Konkrétní požadavek 1>
- [ ] <Konkrétní požadavek 2>

## Akceptační kritéria
- [ ] <Měřitelné kritérium 1>
- [ ] <Měřitelné kritérium 2>

## Poznámky
- Zdroj: <HTTP odkaz na PR>
- Výstup je naformátovaný pro JIRA issue, původní zadání zůstalo obsahově beze změn.
```

## Quality checks
- Verify that original assignment wording in the verbatim section is identical to the source.
- Verify that all unresolved PR review comments are mapped to implementation requirements.
- Verify that acceptance criteria are testable and unambiguous.

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
