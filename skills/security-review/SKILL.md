---
name: security-review
description: "Use when performing comprehensive security review for Laravel/PHP projects. Follows OWASP Top 10 and incident-response hardening checks (packages, malware/webshells, configuration, uploads, credentials), and provides structured reports with severity levels."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Security Review

## Purpose

Perform a comprehensive security review covering OWASP Top 10, incident-response hardening (for Laravel/PHP), and modern threat trends. Produces a structured report with severity-classified findings and actionable remediation steps.

---

## Constraint

- Apply @rules/base-constraints.mdc
- Apply @rules/review-only.mdc
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- Be realistic and precise.
- Never reveal secret values; only report secret categories and exposure risk.

---

## Scripts

Use the pre-built scripts in `@skills/security-review/scripts/` to gather data. Do not reinvent these queries -- run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/check-secrets.sh [dir]` | Scan for hardcoded secrets and committed `.env` files |
| `scripts/scan-php-malware.sh [dir]` | Scan storage/upload dirs for PHP malware and webshell indicators |
| `scripts/composer-audit.sh [dir]` | Run `composer audit` and check known vulnerable packages |

---

## References

- `references/owasp-checklist.md` -- OWASP Top 10 (2021), API Security (2023), and A10:2025 checks
- `references/general-security-checks.md` -- input validation, injection, auth, data exposure, HTTP security, encryption, logging
- `references/threat-trends-2023-2026.md` -- identity attacks, BOLA/IDOR, file uploads, deserialization, supply chain, exploitability-driven remediation
- `references/secret-hygiene.md` -- environment files, secret detection, leak response
- `references/laravel-php-audit.md` -- five-phase Laravel/PHP incident-response audit (packages, malware, config, server, hardening)
- `references/severity-levels.md` -- definitions and examples for Critical, High, Medium, Low

---

## Examples

See `examples/` for expected output formats:
- `examples/report-finding.md` -- single finding with severity, category, location, exploit scenario, and fix
- `examples/report-full-audit.md` -- complete audit report with findings grouped by severity and summary table
- `examples/report-laravel-incident.md` -- Laravel incident-response audit covering all five phases

---

## Steps

1. Review all security rules in `rules/security/*.md`.
2. Review all project rules in `rules/**/*.mdc`.
3. Focus on security risks that static analysis tools cannot detect: business-logic flaws, missing authorization, data flow to sensitive sinks.
4. Run `scripts/check-secrets.sh` to scan for hardcoded secrets and committed environment files.
5. Walk through every check in `references/general-security-checks.md` against the target codebase.
6. Walk through every item in `references/owasp-checklist.md`.
7. Walk through every item in `references/threat-trends-2023-2026.md`.
8. Verify secret hygiene per `references/secret-hygiene.md`.
9. **If the target is Laravel/PHP:**
   a. Run `scripts/composer-audit.sh` to check for vulnerable packages.
   b. Run `scripts/scan-php-malware.sh` to detect malware and webshell indicators.
   c. Follow all five phases in `references/laravel-php-audit.md`.
10. Classify every finding using `references/severity-levels.md`.
11. Produce the final report following the Output contract below.

---

## Output contract

For each security review, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Project name | Yes | Identifies the target project |
| Findings by severity | Yes | Grouped under Critical / High / Medium / Low headings |
| Each finding: severity | Yes | Critical, High, Medium, or Low |
| Each finding: category | Yes | OWASP or rule reference (e.g., A01, A03) |
| Each finding: location | Yes | File path and line number |
| Each finding: description | Yes | What is wrong |
| Each finding: exploit scenario | If applicable | How the vulnerability can be exploited |
| Each finding: recommended fix | Yes | Actionable remediation with code snippet where relevant |
| Summary table | Yes | Total findings count by severity |
| Confidence notes | If applicable | Caveats, assumptions, or areas not covered (e.g., no SSH access for server checks) |
| Action items | Yes | Prioritized checklist of remediation tasks |

**Additional rules:**
- Findings are recommendations; final decisions remain with the human reviewer.
- Provide concrete code snippets for fixes where relevant.
- Never auto-apply changes; provide explicit, actionable recommendations only.
