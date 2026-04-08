---
name: code-review
description: "Reviews code changes to identify bugs, risks, maintainability issues, and improvement opportunities. Provides clear, actionable, severity-prioritized feedback. Works across local diffs, staged changes, branch comparisons, and file reviews. Use when reviewing code, reviewing changes, reviewing a diff, finding issues in code, analyzing modified files, reviewing staged or branch changes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Code Review

## Purpose

Perform senior-level code review on any code changes — local diffs, staged changes, branch comparisons, or specific files. Identify real issues, prioritize findings by severity, provide actionable feedback, and minimize review noise. This skill is read-only — it never modifies code.

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/review-only.mdc
- Always apply @skills/smartest-project-addition/SKILL.md internally to identify one highest-impact, low-risk addition candidate; include it only if it maps to a real finding and keep the final output in the required findings-only format.
- All CR output (findings, recommendations, comments) must be written in English.
- Identify changes vs main branch (list commits).
- Understand context before reviewing.
- Every CR must use @skills/security-review/SKILL.md for the current changes.
- Check for any points where the current changes could break the logic. If it is shared functionality, make sure to check these parts of the application as well!

## Scope Determination

Before starting analysis, the agent MUST identify the review scope:

- **Unstaged changes** — `git diff`
- **Staged changes** — `git diff --cached`
- **Branch diff** — `git diff main...HEAD`
- **Specific file(s)** — review only the provided files
- **Commit range** — `git diff <from>..<to>`
- **Code snippet** — manually provided code

See @references/review-scope-strategies.md for handling each scope type and dealing with missing context.

Do not review code blindly — always determine scope first.

## Context Understanding

Before writing any findings, the agent MUST:

- Understand what changed and why (read the diff, not just final code).
- Compare before/after behavior when possible.
- Consider surrounding code and dependencies.
- Infer intent carefully — acknowledge uncertainty when context is incomplete.
- If reviewing a PR, read the issue description and comment threads.

Do not allow shallow nitpicking without understanding the change.

## Change Analysis

**Steps:**
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- Before writing findings, collect previous CR reports from the related PR/issue discussion and build a dedup list by problem signature (file/scope + risk + root cause). Do not repeat already reported findings unless severity or impact changed.
- **Plan Alignment Analysis:** Compare the implementation against the original issue description, planning documents, or step description. Identify deviations from the planned approach, architecture, or requirements. Assess whether deviations are justified improvements or problematic departures. Verify that all planned functionality has been implemented — list any missing or only partially met items.
- **Simplification analysis:** Evaluate whether the solution can be written more simply without altering the new logic, leveraging rules and conventions already defined in `rules/**/*.mdc`. Flag unnecessary complexity as a finding.
- **Regression analysis:** For every changed file, check whether the modifications could break existing functionality that is NOT part of the ticket scope. Trace callers and dependents of changed methods/classes. If a change alters shared logic (helpers, services, traits, base classes, interfaces), verify that all consumers still behave correctly. Flag any regression risk as a finding — even if the new code is correct in isolation, breaking unrelated features is **Critical**.
- **Security review (every CR):** Always apply @skills/security-review/SKILL.md for the current changes.
- All changes must comply with `rules/**/*.mdc`.
- Apply @rules/architecture-patterns.mdc
- **SQL analysis (only when changes touch the database):** If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed files), use @skills/mysql-problem-solver/SKILL.md for systematic analysis of those parts. If there are no such changes, skip this step.
- **Race condition review (when shared state is modified):** If the changes contain any of the following signals — read-modify-write sequences, shared counters/balances/stock/quotas, `firstOrCreate`/`updateOrCreate`, retried or re-dispatched jobs that mutate shared records, cache write-back patterns, or bulk read-then-write operations — apply @skills/race-condition-review/SKILL.md. If none of these signals are present, skip this step.
- **I/O bottleneck review (when changes touch file, storage, or external I/O):** If the changes include any of the following signals — synchronous file reads/writes on large or unbounded files, blocking HTTP calls without timeouts, storage operations executed in the request lifecycle, large file responses not streamed, or export/import operations loading all records into memory — flag each occurrence and recommend the appropriate async/streaming pattern. If none of these signals are present, skip this step.
- When the task has stated requirements or acceptance criteria (from the issue/PR), verify each item against the changes; list any that are not addressed or only partially met.

## Review Categories

Evaluate changes across these dimensions:

- **Correctness** — logic bugs, incorrect conditions, missed edge cases. See @references/common-bugs.md.
- **Safety** — security issues, data corruption risks, race conditions, injection vulnerabilities.
- **Performance** — inefficient queries, unnecessary computations, N+1 problems, missing indexes. See @references/performance-checks.md.
- **Maintainability** — readability, naming, DRY violations, complexity, mixed responsibilities.
- **Architecture** — separation of concerns, consistency with project patterns, misplaced responsibilities.
- **Testing / Verification** — missing test coverage, lack of validation, fragile tests.

Detailed checks:
- Ensure SRP in each class and apply SOLID principles so that the code is readable for developers.
- **Type safety and defensive programming:** Check for proper error handling robustness, type safety, and defensive programming patterns. Verify guard clauses, null checks, and safe return types.
- Do not duplicate tool checks: types, null safety, formatting, style, naming, dead code, automated refactors.
- Do not review: formatting, import order, lint violations, simple typos — tools cover these.
- Focus only on what tools do not cover: architecture, design, security logic, runtime/operational concerns.
- Prefer `chunk()` or `cursor()` over `get()` for large result sets.
- Inside chunks/cursors: check for N+1; eager-load relations used in the loop.
- Controllers: slim; delegate to Services; accept FormRequest only; never `validate()` in controller.
- Services: hold business logic; return DTOs or models.
- **DTO attribute syntax:** If a Spatie Laravel Data DTO overrides `from()` solely to rename input keys, flag as **Major** and suggest the declarative attribute approach.
- Repositories: read-only. ModelManagers: write-only.
- **`?array` is forbidden (Critical):** Any use of `?array` as a type hint is an error.
- **PHP array key type safety (Major):** Check whether supposed string keys can become integer keys at runtime.
- **Invokeable call syntax (Major):** If code calls an Action via `->__invoke()` instead of direct invocation `$action(...)`, flag and recommend the shorter form.
- Explicitly detect and report **DRY violations** (duplicated logic, duplicated validation rules, repeated branching/condition blocks, and copy-pasted code paths).
- Coverage for changed files only (target 100% for changes). Run tests only for changed files.
- New code is tested: arrange-act-assert; error cases first; descriptive names; data providers via argument; mock only external services. **Prefer partial mocks** over full mocks.
- Laravel: prefer `Http::fake()` over Mockery.

## Severity Levels

Each finding MUST be assigned exactly one severity:

- **Critical** — must be fixed (bugs, security vulnerabilities, data loss, broken behavior).
- **Major** — should be fixed (design flaws, regression risks, significant maintainability issues).
- **Minor** — optional improvement (naming, readability, minor DRY violations).
- **Nitpick** — style or preference (no functional impact).

Do not mix severities. Group output by severity (Critical → Major → Minor → Nitpick).

## Calibrated Reviewing

- Adjust strictness based on change size — small changes get focused review, large changes get broader risk assessment.
- Avoid over-reviewing clean code — if the code is correct and clear, say so briefly.
- Go deeper on risky changes — financial logic, security boundaries, shared infrastructure.
- Label uncertainty explicitly — if you are not sure whether something is a bug, say so.

See @references/review-guidelines.md for writing effective review comments.

## Feedback Construction

Each finding MUST include:

1. **Location** — file and line (or file if line is not precise).
2. **What** is wrong — describe the specific problem.
3. **Why** it is a problem — explain the impact or risk.
4. **Suggested fix** — provide a concrete recommendation (include a short code snippet for simple fixes).

See @examples/review-high-signal.md, @examples/review-mixed-severity.md, @examples/review-clean-change.md, @examples/review-local-diff.md for patterns.

## Forbidden Patterns

- Generic comments ("looks good", "nice work").
- Over-reviewing trivial issues when style tools handle them.
- Hallucinating issues that do not exist in the code.
- Repeating obvious code back to the reviewer.
- Flooding output with weak suggestions.
- Commenting without reasoning.

## Output Contract

```
## Review Scope
<What was reviewed: branch diff, staged changes, specific files, etc.>

## Summary
<One-sentence assessment of overall quality and key risk areas>

## Critical Issues
- <finding with location, description, impact, and fix>

## Major Issues
- <finding with location, description, impact, and fix>

## Minor Issues
- <finding with location, description, impact, and fix>

## Nitpicks
- <finding with location, description, and suggestion>

## Open Questions
- <uncertainties or areas needing clarification>

## Suggestions
- <proactive improvements not tied to specific problems>
```

Rules:
- Prioritize signal over quantity.
- Empty sections are allowed — do not invent findings.
- No duplication across sections.
- If there are no findings, state that no issues were found.

**Confidence notes:** If a finding relies on assumptions about runtime behavior or missing context, append a brief confidence note.

**Deliver:** Output **only findings** (bugs/issues/risks) with a brief suggested fix. No summary, no "what was checked", no praise.

**Communication protocol:**
- Do not include positive feedback or "well done" passages; output must contain only findings.
- If you find significant deviations from the requirements/specification, list them as findings with severity and recommendation.
- For implementation problems, provide clear steps to fix (and a short code example when it speeds up the fix).

**Review best practices:**
- Give concrete fixes or code snippets where relevant; not only "something is wrong".
- Evaluate code in project context and against `rules/**/*.mdc`.
- Findings are recommendations; final decisions remain with the human reviewer.

**After completing the tasks**
- If all **Critical** and **Major** findings from the current CR cycle are resolved, then (and only then) run @skills/test-like-human/SKILL.md when the changes can be tested. The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.
