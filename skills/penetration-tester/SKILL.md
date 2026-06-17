---
name: penetration-tester
description: "Use when the user explicitly requests a penetration test (pentest, ethical hacking, active exploitation, red-team assessment) against an authorized in-scope target — and only then. Runs a methodology-driven offensive assessment that validates exploitability with safe proofs of concept and delivers a risk-rated remediation report. Does not run on a normal code-review, security-review, or resolve-issue pass."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Penetration Tester

## Constraints
- Apply `@rules/security/backend.md` and `@rules/security/frontend.md` (and `@rules/security/mobile.md` when the target ships a mobile app)
- If the target uses Laravel, also apply `@rules/laravel/laravel.mdc`
- Apply `@rules/reports/general.mdc` for the delivered report
- **Read-only / non-destructive** — never modify, stage, commit, or push code; never run a destructive or service-disrupting payload. Output is the assessment report only.
- **Authorization is mandatory** — never test a target without explicit, confirmed authorization and a defined scope. Refuse and stop when scope or authorization is missing.
- Keep every proof of concept minimal and safe; demonstrate impact without causing damage, exfiltrating real data, or persisting access
- Never reveal or store harvested secrets, tokens, or PII in cleartext in the report — redact them
- Hard limits: this file stays <= 500 lines and <= 5000 tokens

## Use when
- The user **explicitly asks** for a penetration test, pentest, ethical-hacking engagement, active exploitation, or red-team assessment
- An authorized, in-scope target (web app, API, network surface, or infrastructure) is named and the rules of engagement are known
- The goal is to **validate real exploitability** and demonstrate impact — not to perform a routine diff review

## Do not use when
- The request is a normal change-set review → use `@skills/security-review/SKILL.md`
- The request is to find unknown reportable bugs for disclosure / bounty → use `@skills/security-bounty-hunter/SKILL.md`
- The request is to remediate a known CVE / advisory → use `@skills/security-threat-analysis/SKILL.md`
- No explicit pentest request, authorization, or scope exists → **refuse and ask** for scope and authorization first

## How this differs from neighbors
- `@skills/security-review/SKILL.md` reviews a diff against best practices (passive, runs every CR); this skill **actively validates exploitability** only on explicit, authorized request.
- `@skills/security-bounty-hunter/SKILL.md` hunts unknown reportable bugs across the reachable surface; this skill runs a **scoped, methodology-driven engagement** with rules of engagement and a phased report.
- `@skills/security-threat-analysis/SKILL.md` remediates a **known** advisory; this skill **discovers and proves** impact.

## Pre-engagement gate (blocking)
Before any active testing, confirm all of the following. If any is missing, stop and ask the user — never proceed on assumption.

1. **Explicit request** — the user actually asked for a penetration test (not a review or audit).
2. **Authorization** — written / confirmed permission to test the named target.
3. **Scope** — the exact in-scope assets (domains, hosts, repos, API endpoints) and the explicit **exclusions**.
4. **Rules of engagement** — testing window, allowed intensity, data-handling rules, and an emergency contact / stop condition.
5. **Success criteria** — what a complete engagement looks like for this request.

Record the confirmed scope and authorization at the top of the report. Refuse any target outside the stated scope.

## Methodology

Run the engagement in ordered phases. Start low-impact, escalate carefully, document everything, and never step outside scope.

### 1. Reconnaissance (passive first)
- Map the attack surface from in-scope evidence only: routes/endpoints, technology fingerprint, exposed services, version markers.
- For a code-accessible target, enumerate entry points: routes (`routes/*.php`, `php artisan route:list`), controllers, FormRequests, Livewire/Filament actions, queued jobs, webhooks, API resources.
- Do not enumerate or probe anything outside the authorized scope.

### 2. Vulnerability identification
Systematically check the high-value classes (OWASP Top 10 oriented):
- **Injection** — SQL (`whereRaw`/`DB::raw`/`DB::statement` with interpolation), command (`exec`/`shell_exec`/`proc_open`/`Process::run`), template, header.
- **Broken access control** — IDOR / BOLA on route-model binding, missing policy/gate checks, privilege escalation, mass-assignment to privileged columns.
- **Authentication & session** — auth bypass, weak session handling, token leakage, missing rate limiting on credential endpoints.
- **SSRF & external interaction** — user-controlled URLs reaching `Http::*` / Guzzle / `file_get_contents`, missing allowlists, internal-IP / metadata reachability.
- **Unsafe deserialization** — `unserialize($userInput)`, unsigned cookie/cache payloads, untrusted `ShouldQueue` sources.
- **XSS** — `{!! $userInput !!}`, Alpine `x-html`, raw JSON in `<script>`.
- **Security misconfiguration & data exposure** — verbose errors leaking stack traces / paths / DB identifiers, unsafe upload handling, path traversal.

### 3. Exploit validation (safe PoC)
- For each candidate, build the **smallest safe proof of concept** that proves impact (a single request or short script).
- Confirm the input is genuinely attacker-controlled all the way to the sink.
- **Never** run a payload that destroys data, disrupts service, persists access, or exfiltrates real user data — demonstrate reachability and impact, then stop.
- Stay strictly within the authorized scope and testing window.

### 4. Impact & risk assessment
- For each validated finding, assess likelihood and business impact, then assign a severity.
- Distinguish validated exploits from theoretical or environmental issues and from false positives.

### 5. Remediation & reporting
- Provide a concrete, actionable fix for every finding, complying with `@rules/security/backend.md`, `@rules/php/core-standards.mdc`, and (for Laravel) `@rules/laravel/architecture.mdc`.
- Separate quick wins from strategic / architectural fixes.

## Severity
- **Critical** — directly exploitable for RCE, auth bypass, mass data access, or full compromise.
- **High** — exploitable with meaningful impact under realistic conditions.
- **Medium** — exploitable but constrained, or requires uncommon preconditions.
- **Low / Informational** — hardening gaps and best-practice deviations with no validated exploit.

## Report

Deliver a single Markdown report with these sections:

### Engagement summary
- Confirmed scope, authorization reference, testing window, and exclusions
- Counts: systems/surfaces tested, findings by severity, exploits validated

### Findings
One block per finding, ordered by severity:
- **Severity** and **category** (OWASP / CWE)
- **Location** — file + line, endpoint, or host (in scope only)
- **Description** — what the vulnerability is and why it matters
- **Proof of Concept** — minimal safe reproduction (redact secrets, tokens, PII)
- **Impact** — what an attacker achieves
- **Remediation** — minimal corrected approach / code that closes the issue

### Risk-rated remediation roadmap
- Quick wins, then strategic / architectural fixes, in priority order

## Quality gate — before delivering
- Authorization and scope were confirmed before any active testing
- Every reported finding has a validated, safe PoC — no theoretical noise reported as exploited
- No destructive, persistent, or service-disrupting action was taken
- Nothing outside the authorized scope was tested
- Secrets, tokens, and PII are redacted in the report
- No code was modified and no unauthorized exploit was run

## Done when
- The pre-engagement gate passed (or the skill refused for missing authorization / scope)
- The methodology phases ran in order within the authorized scope
- Each finding follows the Findings structure and passes every quality-gate item
- The report includes the engagement summary and a risk-rated remediation roadmap

## Related skills
- `@skills/security-review/SKILL.md` — best-practices review of a change set (passive, every CR)
- `@skills/security-bounty-hunter/SKILL.md` — hunt unknown reportable bugs for disclosure
- `@skills/security-threat-analysis/SKILL.md` — remediate a referenced advisory / CVE
