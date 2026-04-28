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

### High
- ...

### Medium
- ...

### Low
- ...

> **Faulty Example, Expected Behavior, and Test Hint are mandatory for every Critical and High finding** so `process-code-review` can turn each finding into a regression test.
> - Faulty Example must be a minimal, runnable snippet or attacker payload — redact real secrets, tokens, and PII with placeholders.
> - Expected Behavior must be a single assertable security guarantee (rejection, authorization denial, escaped output, no side effect).
> - Test Hint must point at the layer the test belongs in (unit, feature, HTTP) and the entry point to call.
> - Medium and Low findings may omit these fields when no behavior change is implied.

### Action Items
1. [ ] ...
