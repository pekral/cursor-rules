---
name: security-review
description: "Use when performing comprehensive security review. Follows OWASP Top 10 and security best practices. Checks for injection vulnerabilities, authentication flaws, sensitive data exposure, misconfigurations, and provides structured security reports with severity levels."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- I want the texts to be in the language in which the task was assigned. Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- NEVER CHANGE THE CODE! Generate the output only.
- All messages formatted as markdown for output.
- Be realistic and precise

**Steps:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Review all security rules in `.cursor/rules/security/*.md`.
- Review all project rules in `.cursor/rules/**/*.mdc`.
- Focus on security risks that static analysis tools cannot detect: business-logic flaws, missing authorization, data flow to sensitive sinks.
- Check Injection vulnerabilities (SQL, command, LDAP, XSS)
- Check Authentication and authorization flaws
- Check Sensitive data exposure
- Check API security misconfigurations
- Check Frontend-specific vulnerabilities (XSS, clickjacking, open redirects)
- Check Mobile-specific vulnerabilities (WebView, insecure storage)
- Check All user inputs are validated and sanitized before processing.
- Check Error handling does not reveal sensitive information (stack traces, internal paths, database details).
- Check No hardcoded secrets (credentials, API keys) in source code or configuration files.
- Check All queries use parameterized queries or ORM — no string concatenation with user input.
- Check Database users follow the principle of least privilege.
- Check Sensitive data is encrypted at rest and in transit.
- Check Content Security Policy (CSP) headers are set.
- Check Cookies use `HttpOnly`, `Secure`, and `SameSite` attributes.
- Check CORS policies are strict and allow only trusted domains.
- Check Anti-CSRF tokens are present for state-changing operations (when using cookie-based auth).
- Check `Origin` and `Referer` headers are validated for non-GET requests.
- Check Authentication and integrity checks on all API requests.
- Check Rate limiting is applied.
- Check Security headers are enforced.
- Check Errors do not reveal sensitive details to end users.
- Check Access and actions are logged for monitoring and auditing.
- Check utbound requests are restricted to necessary services only.
- Check Allowlists define permitted destinations (not blocklists).
- Check User-supplied URLs are validated and sanitized before use.
- Check Request timeouts and rate limits are implemented.
- Check No use of `innerHTML`, `outerHTML`, or `document.write` with dynamic content.
- Check Dynamic content is sanitized with `DOMPurify` or equivalent before DOM insertion.
- Check CSP headers restrict script sources and disable unsafe inline scripts.
- Check Input validation uses allow-lists and well-defined patterns.
- Check User inputs are sanitized before applying to style properties.
- Check Dynamic inline styles are avoided where possible.
- Check CSP uses style nonces or hashes for inline CSS.
- Check `X-Frame-Options` is set to `DENY` or `Content-Security-Policy: frame-ancestors 'none'`.
- Check Frame-busting logic is present.
- Check `SameSite` cookie attributes reduce CSRF exposure across frames.
- Check User input is never used directly in redirects.
- Check Redirect destinations use allowlists or fixed URLs.
- Check Redirect URLs are validated to ensure trusted locations.
- Check External links use `rel="noopener noreferrer"`.
- Check WebView access is limited to trusted URLs.
- Check JavaScript is disabled by default in WebViews.
- Check HTTPS is enforced in WebViews.
- Check WebView data (cache, cookies) is cleared regularly.
- Check nput data is validated before execution in WebViews.
- Check CSP restricts resource types in WebViews.
- Check Sensitive data is not stored in plain text on the device.
- Check No hardcoded secrets in the mobile codebase.
- Check Parameterized queries are used for local database operations.
- Check A01 Broken Access Control** — Authorization checks on every sensitive action; server-side validation; no trust in client-only flags.
- Check **A02 Cryptographic Failures** — Sensitive data encrypted at rest and in transit; no weak algorithms; proper key management.
- Check **A03 Injection** — Parameterized queries; output encoding; no raw user input in SQL, commands, or HTML.
- Check **A04 Insecure Design** — Threat modeling; secure defaults; defense in depth.
- Check **A05 Security Misconfiguration** — Security headers; CORS; error handling; no default credentials.
- Check **A06 Vulnerable Components** — Dependencies up to date; known vulnerabilities checked.
- Check **A07 Authentication Failures** — Strong password policies; MFA where applicable; secure session management; token rotation.
- Check **A08 Data Integrity Failures** — Input validation; signed updates; CI/CD pipeline security.
- Check **A09 Logging Failures** — Security events logged; logs do not contain sensitive data; monitoring and alerting in place.
- Check **A10 SSRF** — Outbound requests validated; allowlists for external services; no unvalidated user-supplied URLs.
- Check No hardcoded secrets (API keys, passwords, tokens) in source code, config files, or environment templates.
- Check `.env` files are excluded from version control (`.gitignore`).
- Check `.env.example` contains only placeholder values — never real credentials.
- Check Git history does not contain leaked secrets (check with `git log` search or tools like Gitleaks).
- Check Use environment variables or secret managers (Vault, AWS Secrets Manager) for sensitive values.
- Check Rotate any secret found in version control immediately.
- CHeck Credential stuffing

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

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
