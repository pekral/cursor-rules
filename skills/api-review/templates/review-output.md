# API Design Review — <Project / PR>

## Critical
1. [file:line] Description
   Rule: @rules/api/general.mdc#<section>
   Risk/Impact: consumer-facing consequence (double-charge, wrong status branch, broken payload shape, bypassed validation).
   Faulty Example:
   ```php
   // minimal route / endpoint / payload snippet that reproduces the issue
   ```
   Expected Behavior: single assertable statement (status code, response shape, idempotent outcome, rejection before side effect).
   Test Hint: one sentence — test layer (feature/HTTP, integration) and entry point.
   Suggested Fix:
   ```php
   // minimal corrected snippet; complies with @rules/api/general.mdc, @rules/php/core-standards.mdc,
   // and @rules/laravel/architecture.mdc on Laravel projects
   ```

## Moderate
- ... (same fields as Critical)

## Minor
- [file:line] Description — Rule: @rules/api/general.mdc#<section> — concrete fix.

> Omit any section that has no findings. Faulty Example, Expected Behavior, Test Hint, and Suggested Fix are mandatory for every Critical and Moderate finding so `@skills/process-code-review/SKILL.md` can turn each into a reproducer test and apply the fix. Minor findings may omit them when no behavior change is implied.
