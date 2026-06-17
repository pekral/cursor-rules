---
name: security-review
description: "Use when performing a focused security review for Laravel/PHP projects. Prioritize real exploitability, business logic flaws, and high-risk vulnerabilities."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/php/dependency-selection.mdc` — when the audit recommends replacing a vulnerable package with a hardened alternative, run the Activity gate + Compatibility gate on the proposed replacement before recommending the swap. Never trade a vulnerable-but-maintained package for an archived / abandoned / branch-pinned one in the name of security.
- Apply `@rules/code-review/general.mdc`
- Apply `@rules/security/backend.mdc`
- Apply `@rules/code-review/frontend.mdc`
- Apply `@rules/code-review/mobile.mdc`
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Apply @rules/reports/general.mdc. When the audit findings are folded into the **GitHub PR comment** by a CR wrapper, they stay in canonical English per the rule's *Exception — technical CR findings on the GitHub PR*. When a non-technical summary is published on a linked issue / JIRA ticket via `@skills/pr-summary/SKILL.md`, it follows the language of the source assignment. CVE / CWE / OWASP identifiers and code identifiers stay verbatim regardless of the surrounding prose language.
- Focus on realistic, exploitable issues
- Never reveal secrets
- **Read-only skill** — never modify code, never stage / commit / push changes, and never run any git write operation (`git add`, `git commit`, `git push`, `git reset`, `git checkout -- …`, etc.). Switching to the relevant branch and `git pull` to read the latest diff are allowed; mutating the working tree or pushing to the remote is not. Output is the audit report only.

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

### Session & Token Management
- session fixation — session ID not rotated after privilege change (login, role elevation)
- missing or weak session expiry / idle timeout
- token reuse — refresh / reset / verification tokens that are not single-use or not invalidated after use
- predictable or low-entropy tokens (sequential IDs, `rand()`, timestamps) where a secret is required
- session cookie flags — `HttpOnly`, `Secure`, `SameSite` missing on auth cookies (cross-check `@rules/security/backend.md` *HTTP Security headers and Cookies*)

### Cryptography & Secrets
- weak or broken algorithms (MD5 / SHA-1 for passwords, ECB mode, `DES`); passwords not hashed with the platform's adaptive hash (`bcrypt` / `argon2` / Laravel `Hash`)
- hardcoded keys, credentials, or salts in source / config (cross-check the *General Secure Coding Practices* secret rule)
- missing encryption-at-rest / in-transit for sensitive fields where the assignment requires it
- non-constant-time comparison of secrets, signatures, or tokens (timing side channel)
- insecure randomness for security values — `rand()` / `mt_rand()` / `uniqid()` instead of `random_bytes()` / `Str::random()`

### Data Exposure
- sensitive data leaks (API, logs, errors)
- unsafe error messages (stack traces, paths, DB details)
- **safe validation & error texts (issue #540)** — walk every user-facing string the diff adds or modifies (FormRequest `messages()` / `attributes()`, exception messages reaching JSON / Inertia / Blade responses, Notification subject and body, Mailable bodies, API error envelopes, flash messages, `__()` / `trans()` / `Lang::get()` / `@lang` / `t()` / `i18next.t()` calls **across every locale shipped by the project** — every key under `lang/`, `resources/lang/`, `translations/`, and locale `*.json` / `*.po` / `*.mo` files) against `@rules/security/backend.md` *Safe Validation & Error Messages* (and `@rules/security/frontend.md` / `@rules/security/mobile.md` for the equivalent client surfaces). Flag — and rewrite in the **Suggested Fix** — any wording that (a) distinguishes which auth factor failed (email vs password vs lock vs 2FA vs verification), (b) confirms a resource exists to an unauthorized caller (replace with the project's generic `404` envelope), (c) interpolates the rejected user input verbatim into the message, (d) reveals stack traces / file paths / framework versions / fully-qualified class names / DB table or column names / SQL fragments / queue or cache driver identifiers, or (e) leaks proximity to the password / token policy rule beyond the rule the user can read. Translation drift — a translated locale reintroducing identity-revealing wording the source removed — is the same finding evaluated per locale. Severity: **Critical** on auth / password-reset / sign-up / authorization surfaces (directly exploitable for enumeration); **Medium** elsewhere. Schema-level validation that does not leak existence ("The age must be at least 18.") is not a finding.

### Security Logging & Monitoring
- security-relevant events not logged (failed logins, authorization denials, privilege changes, password / email changes) where the project already has an audit-log facility to use
- sensitive data written to logs — passwords, tokens, full card / PII — instead of being redacted
- log injection — unsanitized user input written to logs enabling forged or CRLF-split entries
- detection gaps the diff introduces by removing or bypassing an existing audit-trail hook

### External Interaction (APIs & SSRF)
- outbound requests with user-controlled input
- missing domain allowlists
- access to internal/private IPs
- dangerous protocols (`file://`, `gopher://`, etc.)
- missing validation after redirects
- missing rate limiting or abuse protection
- third-party API contract — when the diff integrates with a third-party API or service, verify the security-critical aspects of the implementation against the public API documentation: authentication and scope handling, signature/webhook verification, idempotency and retry semantics, error envelopes, and rate-limit handling. Functional alignment with the issue assignment is owned by `@skills/code-review/SKILL.md` — do not duplicate it here.

### Malicious Code & Supply-Chain Indicators (issue #549)
Walk every line the diff adds or modifies in application code, shell / deploy / CI scripts, `composer.json` / `package.json` script hooks, and installer hooks against `@rules/security/backend.md` *Malicious Code & Supply-Chain Indicators* (and the frontend / mobile mirrors for client surfaces). Raise a finding on each indicator:
- **Silent remote fetch** — `curl -s` / `wget -q` fetching a payload, especially piped to an interpreter (`| sh`, `| bash`, `| php`).
- **Disabled TLS validation** — `curl -k` / `--insecure`, `CURLOPT_SSL_VERIFYPEER => false`, Guzzle `'verify' => false`, `NODE_TLS_REJECT_UNAUTHORIZED=0`, trust-all certificate managers.
- **Suppressed error output** — `2>/dev/null` / `&>/dev/null` on a security-relevant command, `@`-suppressed calls, `error_reporting(0)`, empty `catch {}`.
- **Hidden file + detached background process** — writes to `/tmp` / hidden dot-files combined with `&` / `nohup` / `setsid` / `disown` / detached `proc_open`.

Severity: **Critical** when the indicator maps to active RCE / MITM / persistence (silent fetch piped to a shell, TLS off on a credential request, both halves of the dropper pattern); **High** otherwise. Provide the four reproducer fields per the Critical / High requirement.

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
- **Risk-based severity** — set each finding's severity from likelihood × impact, not the category alone. Likelihood weighs how reachable the entry point is (unauthenticated and public > authenticated > internal-only) and how trivial the exploit is (single request > requires a chained precondition). Impact weighs blast radius (RCE / full account takeover / mass data exposure > single-record leak > low-value disclosure). A theoretically-serious category behind an unreachable path is downgraded; a "minor"-looking flaw on an unauthenticated public endpoint is upgraded. State the likelihood and impact in one phrase in the finding description so the severity is auditable.

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
- **Suggested Fix** — minimal corrected code snippet that closes the vulnerability (parameterized query, authorization check, output escaping, signature verification, safer SDK call, etc.). Must comply with `@rules/php/core-standards.mdc`, `@rules/security/backend.mdc`, and, for Laravel projects, `@rules/laravel/architecture.mdc`. Use `n/a — <reason>` only when the fix is purely configurational (env var, web-server header) and is described in the Recommended Fix narrative.

These fields exist so `@skills/process-code-review/SKILL.md` can turn each finding into a regression test and apply the fix without re-deriving the attack vector. Medium and Low findings may omit them when no behavior change is implied.

### Output format

Use the template defined in `templates/audit-report.md`.
