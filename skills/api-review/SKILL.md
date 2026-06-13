---
name: api-review
description: "Use when reviewing HTTP API design in a PR or change set — endpoints, routes, HTTP methods, status codes, idempotency, and input validation. Treats the API as a consumer-facing contract and flags resource-orientation, method-semantics, status-code, and trust-boundary violations. Read-only."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/api/general.mdc` — this skill is the focused review lens for that rule.
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/security/backend.md` — for the error-text and authorization-leak surface of API responses (401/403/404 wording, no internal-detail leak).
- If the current project uses Laravel, also apply `@rules/laravel/architecture.mdc` and `@rules/laravel/laravel.mdc` — validation belongs in FormRequest / Data Validator, controllers stay slim.
- Apply `@rules/reports/general.mdc` — when the findings are folded into the **GitHub PR comment** by a CR wrapper they stay in canonical English per the rule's *Exception — technical CR findings on the GitHub PR*; a non-technical mirror on a linked issue / JIRA ticket follows the language of the source assignment. HTTP verbs, status codes, header names, and code identifiers stay verbatim regardless of the surrounding prose language.
- Output findings only — no praise, no summary of what was checked.
- **Read-only skill** — never modify code, never stage / commit / push, and never run any git write operation. Switching to the relevant branch and `git pull` to read the latest diff are allowed; mutating the working tree or pushing is not.

## Use when
- A PR or change set adds or modifies HTTP endpoints, routes, controllers, API Resources, request/response payloads, or status-code handling.
- Run as part of every code review via `@skills/code-review/SKILL.md` (Specialized Reviews → Always run).
- A consumer-facing API contract needs a design check before release.

## Scope
Review only the API surface on the **diff** — never untouched endpoints. Detect the surface from any of: route definitions, controller/`__invoke` request handlers, API Resources / DTOs serialized into responses, FormRequests, `response()` / `abort()` / status-code calls, and `Idempotency-Key` handling. If the diff touches no API surface, return no findings.

## Core Checks
Walk the diff against each pillar of `@rules/api/general.mdc` and raise one finding per match.

### 1. Contract & consumer orientation
- Response leaks internal DB structure — raw column names, surrogate/internal keys, join tables, enum integers, or storage-only fields serialized without a DTO / API Resource boundary.
- Inconsistent contract shape for the same concept across endpoints (field casing, date format, pagination shape, error envelope).

### 2. Resource-oriented REST
- Action/verb in the endpoint path (`/getUser`, `/createUser`, `/users/{id}/delete`, `/doPayment`) instead of a resource noun + HTTP method.
- Singular collection nouns or flat URIs where a sub-resource nesting (`/users/{id}/orders`) reads clearer.

### 3. HTTP methods & idempotence
- Method whose side effects violate its contract — `GET` that mutates state, `PUT`/`DELETE` not idempotent on repetition.
- `PUT` used for a partial update or `PATCH` used for a full replacement.

### 4. Idempotency keys
- Critical, retry-prone, state-changing operation (payment, transfer, order placement) with no `Idempotency-Key` handling, so a client retry can double-execute.

### 5. Status codes
- Imprecise success code — `200` for a creation (`201`), for an async hand-off (`202`), or where `204` (no body) is correct; a body returned alongside `204`; a missing `Location` header on `201`.
- Error code collapsed into a generic one where a narrower code applies (`400`/`401`/`403`/`404`/`409`/`422`/`429`).
- 401-vs-403 inversion — `401` for an authorization failure or `403` for a missing/invalid credential.

### 6. Validation at the trust boundary
- Input reaching business logic or the database before schema/business validation runs (trust-boundary bypass).
- Validation inlined in the action/controller/model instead of the dedicated boundary layer (FormRequest / Data Validator).
- Authorization check missing or running before the input is known to be well-formed.
- Error responses that leak identity, resource existence, or internal detail — defer to `@rules/security/backend.md` *Safe Validation & Error Messages* and do not duplicate a finding `@skills/security-review/SKILL.md` already owns.

## Prioritization
- Focus on contract defects a consumer would feel: double-charges, wrong status branching, breaking payload shapes, bypassed validation.
- Deprioritize purely cosmetic naming nits — keep them as **Minor**.
- Do not propose API features the current scope does not require (YAGNI per `@rules/php/core-standards.mdc`).

## Report
Use the severity scale of `@skills/code-review/SKILL.md` so findings fold cleanly into the code review:

- **Critical** / **Moderate** / **Minor** — apply the severity declared in `@rules/api/general.mdc` *CR Severity Rules*.

Each finding includes:
- location (`file:line`)
- risk/impact (the consumer-facing consequence)
- the cited rule reference (e.g. `@rules/api/general.mdc#Resource-Oriented REST`)
- concrete fix

Each **Critical** and **Moderate** finding additionally includes:
- **Faulty Example** — minimal endpoint / route / payload snippet that reproduces the issue (redact secrets/PII)
- **Expected Behavior** — single assertable statement (status code, response shape, idempotent outcome, rejection before side effect)
- **Test Hint** — one sentence pointing at the test layer (feature/HTTP, integration) and the entry point
- **Suggested Fix** — minimal corrected snippet that complies with `@rules/api/general.mdc`, `@rules/php/core-standards.mdc`, and on Laravel projects `@rules/laravel/architecture.mdc`. Use `n/a — <reason>` only when a snippet adds nothing over the one-line fix.

Minor findings may omit these fields when no behavior change is implied.

These fields exist so `@skills/process-code-review/SKILL.md` can turn each finding into a reproducer test and apply the fix without re-deriving context.

## Output Format
Use the template defined in `templates/review-output.md`. Omit any severity section that has no findings; never emit `None.` / `n/a` placeholders.

## Done when
- Every API-surface change on the diff has been walked against the six Core Checks.
- Findings are grouped by severity with the mandatory reproducer fields on every Critical and Moderate item.
- No code, git, or remote state was modified (read-only).
