---
name: security-review
description: "Use when performing comprehensive security review for Laravel/PHP projects. Covers OWASP Top 10, SSRF, auth flaws, data exposure, and incident-response hardening."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply @rules/base-constraints.mdc
- Apply @rules/review-only.mdc
- All output must be in English
- Be precise and realistic
- Never reveal secret values

## Scope
Perform a comprehensive security review with focus on:
- real exploitability
- business logic flaws
- missing authorization
- dangerous data flows

Do not rely on generic best practices alone — prioritize findings based on real risk.

---

## Core Security Checks

### Input & Injection
- SQL, command, LDAP injection
- XSS (stored, reflected, DOM-based)
- unsafe deserialization / parser abuse

### Authentication & Authorization
- missing authorization checks (IDOR / BOLA)
- privilege escalation
- broken access control

### Data Exposure
- sensitive data leaks (API, logs, errors)
- unsafe error messages (stack traces, paths, DB details)

### API Security
- missing auth / auth bypass
- weak CORS
- missing rate limiting
- missing integrity checks

### External Requests (SSRF)
- identify all outbound request sinks:
  - HTTP clients, cURL, file functions
  - webhooks, imports, crawlers, previews

- detect user-controlled input:
  - URL, host, scheme, port, redirects

- verify protections:
  - allowlist of domains
  - block private/internal IP ranges
  - deny dangerous protocols (`file://`, `gopher://`, etc.)
  - validate resolved IP (DNS rebinding protection)
  - re-validate after redirects
  - enforce timeouts and limits

- report:
  - entry point
  - sink
  - reachable internal targets
  - SSRF type (direct / blind)

### File Uploads
- allowlist extensions
- MIME + signature validation
- random filenames
- storage outside webroot
- path traversal protection

### Secrets
- no hardcoded credentials
- `.env` not committed
- `.env.example` safe
- check git history for leaks
- recommend rotation if exposed

### Cryptography
- encryption in transit and at rest
- safe key handling

### Logging & Monitoring
- log security-critical events
- avoid leaking sensitive data in logs

---

## Laravel/PHP Specific Audit

### Dependencies
- analyze `composer.lock`
- check `composer audit`
- flag vulnerable packages
- highlight critical cases (e.g. known vulnerable libs)

### Malware / Webshell Detection
- scan upload/storage paths for executable PHP
- detect suspicious patterns:
  - `eval`, `base64_decode`, `exec`, etc.
- check `.htaccess` anomalies

### Configuration
- verify PHP execution blocked in upload/storage
- check file upload handling
- check credential exposure risk

### Queue & Background Jobs
- check idempotency
- check retry abuse risk
- check unsafe external calls

---

## Modern Threat Coverage (2023–2026)

- ATO resistance (credential stuffing / spraying)
- password reset / OTP hardening
- API resource exhaustion (rate limits, quotas)
- fail-secure behavior
- SSRF bypass techniques
- supply chain risks (SCA, lockfile changes)
- exploitability-based prioritization (CVSS, EPSS, KEV)

---

## Report

### Severity Levels
- Critical
- High
- Medium
- Low

### For each finding include:
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