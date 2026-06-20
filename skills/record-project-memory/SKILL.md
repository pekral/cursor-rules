---
name: record-project-memory
description: "Use when a task has converged (end of resolve-issue, process-code-review, or the final orchestrator report) and a durable, reusable lesson was learned. Distils only the lessons that clear a strict promotion bar and appends them — after a dedup/supersede/prune curation pass — to the per-project compound memory file, never recording trivia, secrets, or PII."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/compound-engineering/general.mdc` *Compound Memory (per project)* — this skill is the write side of that rule; it owns the promotion bar, the curation pass, and the file convention defined there.
- Apply `@rules/git/general.mdc`
- **Write-only to the memory file.** This skill modifies exactly one file: `docs/memory/PROJECT_MEMORY.md` in the project being worked on. It never edits production code, tests, configuration, `CLAUDE.md`, or `.cursor/rules/project.mdc`.
- **Never record secrets, credentials, tokens, or PII** — apply the same prohibition the security rules apply to logs and tracker comments. When a lesson can only be stated with a secret, generalize it until the secret is gone or drop the entry.
- **Per-project only.** The memory file lives in the target project, never in this shared rules package. Never write a project lesson into `rules/**` as a global rule.
- Output must be in English (the memory file is a code artifact, like `CLAUDE.md`).

## Use when
- A `@skills/resolve-issue` or `@skills/process-code-review` run has converged (review loop at 0 Critical / 0 Moderate) and is about to publish its final report.
- An orchestrator (daidalos) reaches its final report after the convergence gate.
- The user explicitly asks to record a lesson into project memory.

Do **not** run this skill mid-task, before convergence, or for a task that produced no durable lesson.

## Inputs
- `CONVERGED_CONTEXT` — required. The converged task context: what was implemented/fixed, the review findings that recurred, the wrong turns taken, and the design decisions made. The orchestrator passes this from the shared brief; a skill passes its own run context.
- `SOURCE_REF` — required. The PR / issue link to record in each entry's `Source:` field.

## Required approach

### 1. Locate (or plan) the memory file
- Target `docs/memory/PROJECT_MEMORY.md` in the project being worked on.
- If it does not exist, plan to create it with a one-line index header (`# Project memory — <project>`). Creating it is allowed; this is the file's first write.
- If the project demonstrably has no `docs/` tree and refuses to grow one, fall back to the project's existing learnings / decision-log file per `@rules/compound-engineering/general.mdc`. Never fall back to `CLAUDE.md` or `.cursor/rules/project.mdc` themselves.

### 2. Extract candidate lessons from the converged context
List every candidate lesson the task surfaced: a recurring review finding, a non-obvious bug cause, a rejected approach, an architectural decision, or the canonical "this kind of change belongs in an existing part of the system, not a new abstraction" insight.

### 3. Apply the promotion bar (all three gates must pass)
Keep a candidate only when it clears **all** of:

1. **Recurs beyond this task** — it generalizes to future work, not a one-off detail of this issue.
2. **Cost real effort to discover** — non-obvious, surprising, or learned after a wrong turn.
3. **Not already captured** — not already obvious from the code, the tests, the git history, or an existing rule.

Drop every candidate that fails any gate. If **no** candidate clears the bar, record nothing and report `no entry: nothing cleared the promotion bar` — a trivial task writes nothing.

### 4. Format each surviving lesson
One entry per lesson, in the greppable format from the rule:

```
### <slug> — <one-line lesson>
- Trigger: <the recurring situation that makes this lesson apply again>
- Rule:    <the decision / what to do next time>
- Example: <a concrete pointer: file / area / symbol>
- Source:  <PR / issue link>   Added: <YYYY-MM-DD>
```

`<slug>` is kebab-case and unique within the file. `Added:` is today's date. Strip any secret / credential / token / PII before writing.

### 5. Curation pass (before appending)
Read the existing file and curate so it stays small and non-redundant:

- **Dedup** — if an equivalent entry exists, do not add a duplicate; strengthen the existing entry only when the new occurrence adds a sharper trigger or example.
- **Supersede** — when a new lesson replaces or contradicts an older entry, update (or mark superseded) the old one instead of keeping both.
- **Prune** — remove entries whose referenced code was removed or whose lesson was promoted into a real `rules/**` file.
- **Soft cap** — when the file grows large, consolidate related entries rather than letting it sprawl.

### 6. Write and report
- Apply the curated changes to the memory file (append new entries, edit/remove superseded or pruned ones).
- Report in conversation: the slugs added, edited, pruned, and the count that failed the promotion bar — so the convergence handoff records what memory changed.

## Done when
- Every candidate lesson was run through the three-gate promotion bar.
- Surviving lessons were written to `docs/memory/PROJECT_MEMORY.md` in the entry format, after the curation pass deduped/superseded/pruned.
- No secrets, credentials, tokens, or PII were written.
- Only the memory file was modified — no production code, tests, `CLAUDE.md`, or `.cursor/rules/project.mdc`.
- The report lists the slugs added/edited/pruned, or states `no entry: nothing cleared the promotion bar`.

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
