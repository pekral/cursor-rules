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

### Output format
```markdown
## Security Audit Report - <Project Name>

### Critical
- ...

### High
- ...

### Medium
- ...

### Low
- ...

### Action Items
1. [ ] ...

```
