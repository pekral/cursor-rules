## Previous CR Status

> Include this section only in follow-up reviews when a previous CR exists for the same PR. Omit entirely for first reviews.

| # | Finding | Status |
|---|---------|--------|
| 1 | Previous finding description | ✅ Resolved / ⏳ Deferred / ❌ Still open |

---

## Critical

1. [file:line] Description
   Impact: ...
   Fix: ...
   Faulty Example:
   ```php
   // minimal code or input that reproduces the issue
   ```
   Expected Behavior: what the correct outcome (return value, exception, side effect) must be.
   Test Hint: one-sentence outline of the test that would fail today and pass after the fix.
   Suggested Fix:
   ```php
   // minimal corrected snippet that resolves the finding; must comply with @rules/php/core-standards.mdc (and @rules/laravel/architecture.mdc on Laravel projects)
   ```

## Moderate

1. ...

## Minor

1. ...

## Refactoring (DRY / Tech Debt Reduction)

> Include only items that apply to lines actually touched by this PR (added or modified). Never review untouched code here. Each item must reduce technical debt — no stylistic preferences.

1. [file:line] DRY duplication or structural problem in the changed code
   Suggested refactoring: concrete consolidation step (Data Builder, DTO, Service, Action, Repository, etc.)
   Why: which rule from `@rules/laravel/architecture.mdc` or `@skills/class-refactoring/SKILL.md` is satisfied by the change.

> **Faulty Example, Expected Behavior, Test Hint, and Suggested Fix are mandatory for every Critical and Moderate finding** so `process-code-review` can turn each finding into a reproducer test and apply the fix directly from the PR comment.
> - Faulty Example must be a minimal, runnable snippet (or sample input/payload) — never paste secrets or real PII; redact with placeholders.
> - Expected Behavior must be a single assertable statement (return value, thrown exception, persisted state, emitted event).
> - Test Hint must point at the layer the test belongs in (unit, integration, feature) and the entry point to call.
> - Suggested Fix must be a minimal corrected snippet that complies with `@rules/php/core-standards.mdc` and, on Laravel projects, with `@rules/laravel/architecture.mdc`. Use `n/a — <reason>` only when a snippet adds no value over the one-line Fix description.
> - Minor findings may omit these fields when no behavior change is implied (e.g. naming, dead code).

## Refactoring Proposals

1. **Title:** short, actionable issue title
   **Scope:** affected file(s) or area
   **Reason:** which rule or principle is violated and why it matters
   **Suggested approach:** brief description of the expected refactoring

Only propose refactoring justified by defined rules — not stylistic preferences.
Omit this section if no opportunities are found.

## Coverage

> Mandatory. Never post a CR comment without this section.

- **Tool:** name of the discovered coverage tool (Phing target, Composer script, etc.) or "not available" with reason.
- **Command:** exact command that was executed.
- **Result:** % covered for changed lines (or list any uncovered added/changed lines, which must also appear as Critical findings).

**Summary: X Critical, Y Moderate, Z Minor, R Refactoring (DRY / Tech Debt Reduction)**
