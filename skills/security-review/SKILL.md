---
name: security-review
description: "Use when performing comprehensive security review for Laravel/PHP projects. Follows OWASP Top 10 and incident-response hardening checks (packages, malware/webshells, configuration, uploads, credentials), and provides structured reports with severity levels."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- **Before starting the review**, analyze all comments and discussions in the issue so that you fully understand what the final state should be and what logic should have been created. Only then begin reviewing.
- I want the texts to be in the language in which the task was assigned. Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- NEVER CHANGE THE CODE! Generate the output only.
- All messages formatted as markdown for output.
- Be realistic and precise.
- Never reveal secret values; only report secret categories and exposure risk.

**Steps:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Review all security rules in `.cursor/rules/security/*.md`.
- Review all project rules in `.cursor/rules/**/*.mdc`.
- Focus on security risks that static analysis tools cannot detect: business-logic flaws, missing authorization, data flow to sensitive sinks.
- Check injection vulnerabilities (SQL, command, LDAP, XSS).
- Check authentication and authorization flaws.
- Check sensitive data exposure.
- Check API security misconfigurations.
- Check frontend-specific vulnerabilities (XSS, clickjacking, open redirects).
- Check mobile-specific vulnerabilities (WebView, insecure storage).
- Check that all user input is validated and sanitized before processing.
- Check that error handling does not reveal sensitive information (stack traces, internal paths, DB details).
- Check for hardcoded secrets (credentials, API keys, tokens) in source code or configuration.
- Check that all DB queries use parameterized APIs or ORM (no string concatenation with user input).
- Check least privilege for database and service accounts.
- Check encryption in transit and at rest for sensitive data.
- Check CSP, CORS, CSRF, rate limiting, session/cookie flags (`HttpOnly`, `Secure`, `SameSite`), and security headers.
- Check outbound request controls (allowlists, URL validation, timeouts).
- Check logging and monitoring of security-relevant events without leaking sensitive data.
- Check OWASP coverage:
  - **A01 Broken Access Control**
  - **A02 Cryptographic Failures**
  - **A03 Injection**
  - **A04 Insecure Design**
  - **A05 Security Misconfiguration**
  - **A06 Vulnerable and Outdated Components**
  - **A07 Identification and Authentication Failures**
  - **A08 Software and Data Integrity Failures**
  - **A09 Security Logging and Monitoring Failures**
  - **A10 SSRF**
- Check secret hygiene:
  - `.env` not committed,
  - `.env.example` contains placeholders only,
  - git history checked for leaks,
  - rotate any exposed secret immediately.
- Check identity attack resistance (credential stuffing/spraying throttles, abuse detection, safe lockout).
- Check password reset/OTP hardening (single use, short lifetime, retry limits).
- Check BOLA/IDOR regression coverage on identifier-based endpoints.
- Check API resource consumption limits (OWASP API4:2023).
- Check file upload hardening (allowlist, MIME/signature checks, random names, storage outside webroot).
- Check unsafe deserialization/parser abuse (`unserialize`, risky parsers) and fail-secure exception behavior.
- Check supply chain controls (SCA in CI, lockfile review, SBOM where applicable).

## Laravel/PHP Incident-Response Audit Extension

When the target is Laravel/PHP, include the following phases in the audit:

### Phase 1: Vulnerable Package Detection
- Analyze `composer.lock` and run `composer audit` (if available).
- Flag `intervention/image` versions lower than v3 as **Critical** (known path traversal risk).
- Check if vulnerable packages are direct or transitive dependencies.
- Evaluate safe upgrade strategy and potential breaking changes.

### Phase 2: Malware and Webshell Detection
- Search for PHP files in upload/public storage locations where execution should not be possible.
- Flag suspicious filenames and patterns (random 5-char names, common malware filenames, double extensions, temporary PHP droppers).
- Scan for dangerous PHP execution patterns (`eval`, `base64_decode`, `exec`, `system`, `shell_exec`, `assert`, etc.) in suspicious contexts.
- Check miner indicators (files/process names such as `xmrig`, `minerd`, `stmept`) and known pool references.
- Inspect `.htaccess` files for injected redirects, proxy directives, or shell-related artifacts.

### Phase 3: Configuration Security Check
- Verify `storage/app/public/.htaccess` blocks PHP execution.
- Verify `public/.htaccess` blocks PHP execution in public file areas and protects sensitive files.
- Review upload code for risky patterns (for example: `editableSvgs(true)`, unsafe filename preservation, weak MIME/extension checks).
- Inspect credential exposure risk in environment/config files without printing actual secret values.

### Phase 4: Server-Level Checks (When Access Exists)
- Verify web server/PHP processes run as unprivileged users.
- Audit risky sudoers entries (`NOPASSWD: ALL`).
- Check cron jobs for persistence/backdoor behavior.
- Check active suspicious processes and unusual open ports associated with miners/backdoors.

### Phase 5: Hardening Recommendations
- Propose safe hardening snippets for Apache/Nginx (deny PHP in upload/storage paths, add security headers, deny sensitive file patterns).
- For potentially breaking dependency remediations, provide a migration plan and impact notes.
- Never auto-apply changes in this skill; provide explicit, actionable recommendations only.

**Deliver:** A structured security report organized by severity.

**Severity levels:**
- **Critical** — Exploitable vulnerabilities requiring immediate action (injection, auth bypass, data exposure).
- **High** — Significant risks that should be addressed promptly (missing CSRF, weak CORS, open redirects).
- **Medium** — Best practice violations that increase attack surface (missing headers, weak validation).
- **Low** — Minor improvements for defense in depth (logging gaps, informational leaks).

**Report format:**
- List each finding with: severity, category (OWASP/SecureCodeWarrior rule), location (file and line), description, exploit Scenario (if applicable), and recommended fix.
- Provide concrete code snippets for fixes where relevant.
- Summarize total findings by severity at the end.
- Findings are recommendations; final decisions remain with the human reviewer.

## Example Audit Output Format

Issue: Missing Authorization Check  
Risk: High

Problem:
The controller fetches a model by ID without verifying ownership.

Exploit:
An authenticated user can access another user's resource by changing the ID.

Fix:
Use policy check or scoped query.

Refactored Example:

```php
$post = Post::where('user_id', auth()->id())
    ->findOrFail($id);
```

## Report Template for Laravel/PHP Projects

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
2. [ ] ...
```

## Additional checks from 2023-2026 threat trends

- Check **Identity attack resistance (ATO)** on login and recovery flows: anti-automation controls for credential stuffing/spraying, per-account and per-IP throttling, step-up challenge, and safe lockout behavior.
- Check **Password reset and OTP hardening**: reset/OTP tokens are single-use, short-lived, and protected by retry limits and abuse detection.
- Check **BOLA/IDOR regression coverage**: every endpoint with object identifiers has a negative authorization test proving out-of-scope IDs return `403/404`.
- Check **Unrestricted resource consumption (OWASP API4:2023)**: expensive endpoints have limits (rate, quota, pagination bounds, payload size), and return `429` when limits are exceeded.
- Check **File upload hardening**: extension allowlist, content-type verification by actual file signature, random file names, storage outside webroot, and path traversal protections.
- Check **Unsafe deserialization and parser abuse**: no deserialization of untrusted input (`unserialize`, unsafe object parsers, unsafe binary formats) without strict type allowlists and validation.
- Check **Fail-secure exception handling (OWASP A10:2025)**: exception paths do not bypass authz/authn and default to deny/abort.
- Check **Security logging vocabulary and coverage**: security-critical events (login lifecycle, MFA changes, role changes, admin actions, exports) are logged with a consistent schema and are alertable.
- Check **Exploitability-driven remediation priority** for CVE findings using:
  - `CVSS` severity,
  - `EPSS` probability,
  - `CISA KEV` presence,
  - real exposure (internet-facing/reachable path),
  - asset criticality.
- Check **Urgency thresholds for patching**: findings with KEV + internet exposure, or very high exploitability score, are escalated to immediate hotfix workflow.
- Check **Supply chain release controls**: SBOM generation per release, SCA in CI, policy-based build failure for high-risk exploitable dependencies, and lockfile change review.
- Check **Secret leak response readiness**: if leaked secrets are detected, incident flow includes immediate revocation/rotation and audit of dependent systems.
