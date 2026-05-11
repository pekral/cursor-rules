## Security Audit Report - <Project Name>

### Critical
1. [file:line] Description
   Category (OWASP): ...
   Exploit Scenario: ...
   Recommended Fix: ...
   Faulty Example:
   ```php
   // minimal code or attacker-supplied payload that reproduces the vulnerability
   ```
   Expected Behavior: what the application must do instead (rejected input, thrown exception, denied authorization, sanitized output).
   Test Hint: one-sentence outline of the security test (request shape, assertion target, layer).
   Suggested Fix:
   ```php
   // minimal corrected snippet that closes the vulnerability; must comply with @rules/php/core-standards.mdc, @rules/security/backend.mdc, and @rules/laravel/architecture.mdc on Laravel projects
   ```

### High
- ...

### Medium
- ...

### Low
- ...

> **Faulty Example, Expected Behavior, Test Hint, and Suggested Fix are mandatory for every Critical and High finding** so `process-code-review` can turn each finding into a regression test and apply the fix from the report.
> - Faulty Example must be a minimal, runnable snippet or attacker payload — redact real secrets, tokens, and PII with placeholders.
> - Expected Behavior must be a single assertable security guarantee (rejection, authorization denial, escaped output, no side effect).
> - Test Hint must point at the layer the test belongs in (unit, feature, HTTP) and the entry point to call.
> - Suggested Fix must be a minimal corrected snippet that closes the vulnerability and complies with `@rules/php/core-standards.mdc`, `@rules/security/backend.mdc`, and (on Laravel projects) `@rules/laravel/architecture.mdc`. Use `n/a — <reason>` only when the fix is purely configurational and already described in Recommended Fix.
> - Medium and Low findings may omit these fields when no behavior change is implied.

### Action Items
1. [ ] ...
