---
name: security-review
description: "Use when performing a focused security review for Laravel/PHP projects. Prioritize real exploitability, business logic flaws, and high-risk vulnerabilities."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/code-review/general.mdc`
- Apply `@rules/security/backend.mdc`
- Apply `@rules/code-review/frontend.mdc`
- Apply `@rules/code-review/mobile.mdc`
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Output must be in English
- Focus on realistic, exploitable issues
- Never reveal secrets

## Scope
Perform a focused security review with emphasis on:
- real exploitability
- business logic flaws
- missing authorization
- unsafe data flows

Avoid generic best-practice noise.

---

## Core Checks

### Input & Injection
- SQL / command injection
- XSS (stored, reflected, DOM)
- unsafe deserialization

### Authentication & Access Control
- missing authorization (IDOR / BOLA)
- privilege escalation
- broken access control

### Data Exposure
- sensitive data leaks (API, logs, errors)
- unsafe error messages (stack traces, paths, DB details)

### External Interaction (APIs & SSRF)
- outbound requests with user-controlled input
- missing domain allowlists
- access to internal/private IPs
- dangerous protocols (`file://`, `gopher://`, etc.)
- missing validation after redirects
- missing rate limiting or abuse protection
- third-party API contract — when the diff integrates with a third-party API or service, verify the implementation against the public API documentation: authentication and scope handling, signature/webhook verification, idempotency and retry semantics, error envelopes, and rate-limit handling. Flag any divergence from the issue assignment or missing in-scope use cases that the API exposes.

### File Handling
- unsafe uploads (extension, MIME, signature)
- path traversal
- execution risk (files in webroot)

### Dependencies & Configuration
- vulnerable packages (`composer.lock`, `composer audit`)
- unsafe configuration (uploads, execution, credentials)

### Queues & Background Jobs
- retry abuse
- non-idempotent operations
- unsafe external calls

---

## Prioritization
- Focus on issues that are:
  - exploitable in real scenarios
  - impactful (data access, privilege escalation, RCE)
- Deprioritize theoretical or low-impact findings

---

## Report

### Severity
- Critical
- High
- Medium
- Low

### Each finding must include
- severity
- category (OWASP)
- location (file + line)
- description
- exploit scenario
- recommended fix

### Reproducer fields (mandatory for Critical and High)
- **Faulty Example** — minimal code snippet or attacker payload that reproduces the vulnerability (redact secrets, tokens, and PII)
- **Expected Behavior** — single assertable security guarantee (rejection, authorization denial, escaped output, no side effect)
- **Test Hint** — one sentence pointing at the test layer (unit, feature, HTTP) and entry point

These fields exist so `@skills/process-code-review/SKILL.md` can turn each finding into a regression test without re-deriving the attack vector. Medium and Low findings may omit them when no behavior change is implied.

### Output format

Use the template defined in `templates/audit-report.md`.
