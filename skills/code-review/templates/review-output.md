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

## Moderate

1. ...

## Minor

1. ...

## Refactoring (DRY / Tech Debt Reduction)

> Include only items that apply to lines actually touched by this PR (added or modified). Never review untouched code here. Each item must reduce technical debt — no stylistic preferences.

1. [file:line] DRY duplication or structural problem in the changed code
   Suggested refactoring: concrete consolidation step (Data Builder, DTO, Service, Action, Repository, etc.)
   Why: which rule from `@rules/laravel/architecture.mdc` or `@skills/class-refactoring/SKILL.md` is satisfied by the change.

> **Faulty Example, Expected Behavior, and Test Hint are mandatory for every Critical and Moderate finding.** They feed `process-code-review` so each fix can be backed by a reproducer test.
> - Faulty Example must be a minimal, runnable snippet (or sample input/payload) — never paste secrets or real PII; redact with placeholders.
> - Expected Behavior must be a single assertable statement (return value, thrown exception, persisted state, emitted event).
> - Test Hint must point at the layer the test belongs in (unit, integration, feature) and the entry point to call.
> - Minor findings may omit these fields when no behavior change is implied (e.g. naming, dead code).

## Refactoring Proposals

If any reviewed code violates project rules (`@rules/php/core-standards.mdc`, `@rules/laravel/architecture.mdc`) or has clear structural issues that are **out of scope** for the current PR, propose a new issue for each refactoring opportunity:

1. **Title:** short, actionable issue title
   **Scope:** affected file(s) or area
   **Reason:** which rule or principle is violated and why it matters
   **Suggested approach:** brief description of the expected refactoring

Only propose refactoring that is justified by defined rules or architecture — not stylistic preferences.
If no refactoring opportunities are found, omit this section.

**Summary: X Critical, Y Moderate, Z Minor, R Refactoring (DRY / Tech Debt Reduction)**
