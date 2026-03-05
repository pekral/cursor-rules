---
name: security-review
description: "Performs comprehensive security review following OWASP Top 10 and security best practices. Checks injection, auth flaws, sensitive data exposure, misconfigurations; outputs structured reports by severity. Use when the user requests a security review or when part of a code/PR review. Do not use for implementing fixes or for non-security code review."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Use the same language as the assignment.
- Do not change code; produce review output only.
- Format all output as markdown.
- Be realistic and precise.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Review security-related rules in `.cursor/rules/**/*.mdc` and any `.cursor/rules/security/*.md` if present.
3. Focus on security risks that static analysis may not detect: business-logic flaws, missing authorization, data flow to sensitive sinks.
4. Check: injection (SQL, command, LDAP, XSS); authentication and authorization flaws; sensitive data exposure; API security misconfigurations; frontend (XSS, clickjacking, open redirects); mobile (WebView, insecure storage).
5. Check: all user inputs validated and sanitized; error handling does not reveal sensitive information (stack traces, internal paths, DB details); no hardcoded secrets (credentials, API keys) in source or config; all queries use parameterized queries or ORM (no string concatenation with user input); database users follow least privilege; sensitive data encrypted at rest and in transit; CSP headers set; cookies use `HttpOnly`, `Secure`, `SameSite`; CORS strict and allow only trusted domains; anti-CSRF tokens for state-changing operations (cookie-based auth); `Origin` and `Referer` validated for non-GET requests; authentication and integrity checks on all API requests; rate limiting; security headers; errors do not reveal sensitive details to end users; access and actions logged for monitoring; outbound requests restricted to necessary services; allowlists (not blocklists) for destinations; user-supplied URLs validated and sanitized; request timeouts and rate limits; no `innerHTML`/`outerHTML`/`document.write` with dynamic content; dynamic content sanitized (e.g. DOMPurify) before DOM insertion; CSP restricts script sources and disables unsafe inline scripts; input validation uses allowlists and well-defined patterns; user input sanitized before style properties; avoid dynamic inline styles where possible; CSP style nonces or hashes for inline CSS; `X-Frame-Options` DENY or `frame-ancestors 'none'`; frame-busting where relevant; `SameSite` for cookies; user input never used directly in redirects; redirect destinations use allowlists or fixed URLs; redirect URLs validated; external links use `rel="noopener noreferrer"`; WebView limited to trusted URLs; JavaScript disabled by default in WebViews; HTTPS enforced in WebViews; WebView cache/cookies cleared regularly; input validated before execution in WebViews; CSP restricts resource types in WebViews; no sensitive data in plain text on device; no hardcoded secrets in mobile code; parameterized queries for local DB operations.
6. Check OWASP: A01 Broken Access Control (authorization on every sensitive action; server-side validation; no trust in client-only flags); A02 Cryptographic Failures (encryption at rest/transit; no weak algorithms; key management); A03 Injection (parameterized queries; output encoding; no raw user input in SQL/commands/HTML); A04 Insecure Design (threat modeling; secure defaults; defense in depth); A05 Security Misconfiguration (headers; CORS; error handling; no default credentials); A06 Vulnerable Components (dependencies up to date; known vulnerabilities checked); A07 Authentication Failures (strong password policy; MFA where applicable; secure session; token rotation); A08 Data Integrity (input validation; signed updates; CI/CD pipeline security); A09 Logging Failures (security events logged; no sensitive data in logs; monitoring/alerting); A10 SSRF (outbound requests validated; allowlists; no unvalidated user-supplied URLs).
7. Check: no hardcoded secrets in source, config, or env templates; `.env` in `.gitignore`; `.env.example` has only placeholders; git history free of leaked secrets (e.g. Gitleaks); use env vars or secret managers (Vault, AWS Secrets Manager); rotate any secret found in version control immediately.

**Deliver** A structured security report organized by severity.

**Severity levels**
- **Critical** — Exploitable vulnerabilities requiring immediate action (injection, auth bypass, data exposure).
- **High** — Significant risks to address promptly (missing CSRF, weak CORS, open redirects).
- **Medium** — Best practice violations that increase attack surface (missing headers, weak validation).
- **Low** — Minor improvements for defense in depth (logging gaps, informational leaks).

**Report format**
- For each finding: severity, category (OWASP/rule), location (file and line), description, exploit scenario (if applicable), recommended fix.
- Provide concrete code snippets for fixes where relevant.
- Summarize total findings by severity at the end.
- Findings are recommendations; final decisions remain with the human reviewer.

**Example audit output format**

Issue: Missing Authorization Check  
Risk: High

Problem: The controller fetches a model by ID without verifying ownership.

Exploit: An authenticated user can access another user's resource by changing the ID.

Fix: Use policy check or scoped query.

Refactored example:

```php
$post = Post::where('user_id', auth()->id())
    ->findOrFail($id);
```
