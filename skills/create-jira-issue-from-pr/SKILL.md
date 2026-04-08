---
name: create-jira-issue-from-pr
description: "Use when preparing a JIRA issue draft from GitHub pull request context while preserving the original assignment text and making the output understandable for both AI agents and non-technical stakeholders."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Create JIRA Issue From PR

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Apply @rules/jira-operations.mdc
- Never use a web browser for issue and PR analysis when CLI/MCP tools are available.
- Keep the original assignment text unchanged; only improve formatting and structure.

**Scripts:** Use the pre-built scripts in `@skills/create-jira-issue-from-pr/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/fetch-pr-context.sh <PR>` | Fetch full PR context: body, diff stats, commits, linked issues |
| `scripts/fetch-pr-comments.sh <PR>` | Fetch all reviews, comments, and review threads |
| `scripts/create-jira-issue.sh <PROJECT> <SUMMARY> <BODY_FILE>` | Create JIRA issue and assign to current user |

**References:**
- `references/drafting-rules.md` — rules for each section of the JIRA issue draft, goal summary, verbatim handling, requirements
- `references/quality-checks.md` — verbatim integrity, PR comment coverage, acceptance criteria validation, attachment verification
- `references/attachment-handling.md` — detecting, retrieving, analyzing, and including attachments in the draft

**Examples:** See `examples/` for expected output format:
- `examples/issue-draft-complete.md` — full issue draft with review comments and requirements
- `examples/issue-draft-minimal.md` — minimal draft when no review comments exist
- `examples/issue-draft-with-attachments.md` — draft including analyzed attachments

**Steps:**
1. Run `scripts/fetch-pr-context.sh <PR>` to collect PR body, diff stats, commits, and linked issues.
2. Run `scripts/fetch-pr-comments.sh <PR>` to collect review comments and review thread context.
3. Analyze repository context before drafting output:
   - Inspect the PR diff and related commits.
   - Include relevant implementation context needed for delivery.
4. If attachments are referenced, download and analyze them per `references/attachment-handling.md`.
5. Build a task list from PR comments and assignment context:
   - Include unresolved requirements only.
   - Remove already-resolved or duplicate requests.
6. Prepare a JIRA-ready markdown issue draft per `references/drafting-rules.md`:
   - Preserve original assignment text exactly (verbatim section).
   - Add a clear goal summary understandable for non-technical readers.
   - Add a technical section for AI agents and developers.
   - Keep acceptance criteria concrete and testable.
7. Run quality checks per `references/quality-checks.md`.
8. If user asks for creation, run `scripts/create-jira-issue.sh` to create the issue in JIRA, assign it to the current user, and return the direct issue URL.

**Output contract:** For each generated JIRA issue draft, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Cíl (Goal) | Yes | Brief summary understandable for non-technical audience |
| Původní zadání | Yes | Verbatim original assignment text, unchanged |
| Technický kontext z PR | Yes | Summary of relevant technical findings from PR |
| Požadavky pro implementaci | Yes | Checklist of concrete implementation requirements |
| Akceptační kritéria | Yes | Testable and measurable acceptance criteria |
| Poznámky | Yes | Source PR link, formatting note, caveats |
| Confidence notes | If applicable | Assumptions, missing attachments, ambiguous comments |

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

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
