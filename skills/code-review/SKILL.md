---
name: code-review
description: Use when senior PHP code review focused on architecture, business
  logic, and risk detection. Read-only.
---

# Code Review

## Purpose
Perform structured code review focused on:
- correctness
- architecture
- regression risks
- security and performance issues

---

## Constraints
- Apply @rules/php/core-standards.mdc
- Apply @rules/code-review/general.mdc
- Apply @rules/refactoring/general.mdc — use the shared refactoring definition when assessing refactoring changes or when proposing refactoring; reject big-bang rewrites and prefer incremental migration.
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Output findings only (no praise)
- Never modify code
- All output must be in English
- Do not review formatting, linting, or trivial issues

---

## Execution

- Before starting, ensure you are on the branch that contains the changes to review. If not, switch to it.
- Identify changes vs main branch.
- Deduplicate previous findings.

### Previous CR Analysis

If a previous code review exists for the same PR:

1. Load all previous CR findings from PR comments.
2. Classify each previous finding into one of these statuses:
   - **Resolved** — the finding was fixed in subsequent commits.
   - **Deferred** — the finding was acknowledged but intentionally left for a future task.
   - **Still open** — the finding was not addressed and remains valid.
3. Include this classification as a **Previous CR Status** section in the output (before new findings).

### Issue Context Analysis

Before reviewing code, load and analyze the full issue context:

1. Load the complete issue or task (description, all comments, and attachments) from the linked tracker (GitHub, JIRA, Bugsnag).
2. Extract from the issue:
   - **Requirements and acceptance criteria** — what the code must do
   - **Expected behavior** — how the feature or fix should work
   - **Edge cases and constraints** — mentioned by the reporter or in comments
   - **Test data** — any sample inputs, payloads, or scenarios provided in the issue
3. Use this context to evaluate whether the implementation fully satisfies the issue — not just whether the code is technically correct.
4. If the issue contains test data or test scenarios, verify they are covered by existing or new tests. Flag missing test coverage as a finding.

### Third-Party API & Service Analysis

Run this section only when the diff integrates with, modifies, or depends on a third-party API or external service (HTTP clients, vendor SDK calls, webhooks, OAuth flows, payload schemas, queue/event consumers backed by external systems).

1. Identify every affected API or service from the diff and list the concrete endpoints, SDK methods, webhook events, or message contracts that changed.
2. Locate the official public reference for each one — vendor documentation, OpenAPI/Swagger spec, SDK reference, or webhook contract. Prefer URLs cited in the issue or PR; otherwise look up the vendor's current published documentation for the version in use.
3. Compare the implementation against the public contract:
   - endpoints, HTTP methods, and required vs optional parameters
   - request and response schemas, status codes, and error envelopes
   - authentication, scopes, rate limits, idempotency keys, and retry semantics
   - pagination, filtering, sorting, webhook signatures, and timeouts
4. Cross-check the implementation against the issue assignment — verify the chosen endpoints, parameters, and behaviors satisfy what the issue actually asked for. Flag any divergence (missing endpoint, wrong verb, ignored field, fabricated parameter) as a finding.
5. Confirm coverage of every API use case that is in scope for the issue — documented filters, status branches, error states, and edge inputs the issue explicitly or implicitly requires. Missing in-scope use cases are findings. Do not propose adopting API features that current scope does not require (YAGNI per `@rules/php/core-standards.mdc`); only when the diff exposes an out-of-scope structural shortcoming in how the project consumes the API (e.g. missing webhook signature verification across other consumers) raise it under **Refactoring Proposals**.
6. If the public reference cannot be located, accessed, or matched to the version in use, raise this as a **Moderate** finding instead of silently assuming the contract.

### Core Analysis
- Regression risk (shared logic, dependencies)
- Architecture and design quality
- Business logic correctness
- Missing or incorrect behavior
- Type safety and error handling
- Per-row DB operations in loops — flag any loop that issues per-row `update()`, `create()`, `delete()`, or single-row read; require batching via ModelManager `batchUpdate` / `batchInsert`, `whereIn(...)->delete()`, or a single bulk read keyed in memory (see `@rules/sql/optimalize.mdc` "Batch over per-row operations"). Allowed only when an explicit code comment or PR note justifies an unavoidable per-row side-effect dependency.
- Third-party API/service contract — when changes touch external APIs or services, verify the implementation matches the public API documentation, satisfies the issue assignment, and covers all relevant in-scope API use cases (see **Third-Party API & Service Analysis** section)
- Refactoring quality — when changes are refactoring in nature, validate them against `@rules/refactoring/general.mdc`: behavior must be preserved, migration must be incremental (no big-bang rewrites), and entry points / responsibilities / DRY / concurrency must follow the recommended process. In Laravel projects, combine with `@rules/laravel/architecture.mdc`.
- Data validation encapsulation — verify that all validation logic is in dedicated Data Validator classes or FormRequests (using validation rules from reusable traits in `app/Concerns/`), not inline in Actions, controllers, jobs, commands, listeners, or Livewire components (see `@rules/laravel/architecture.mdc` Data Validators section)
- Repository scope — verify Repositories expose only basic, reusable queries (`find`, `findBy{Attribute}`, `all`, simple `where` lookups, pagination of a base scope). Feature-specific or use-case–specific query methods in Repositories are a finding; specialization belongs in a Service (single-model) or Action (cross-model / cross-feature) composing basic Repository methods (see `@rules/laravel/architecture.mdc` Repositories and ModelManagers section)
- Data Modification (DRY) — enumerate every place in the changes that modifies data before it is saved or passed downstream (DTO mapping, payload shaping, key renaming, default fallbacks, format normalization, business-driven derivation). Cross-check against existing entry points; if the same shaping appears in more than one Action / Service / controller / job / listener / Livewire component / command, flag it and require consolidation into the canonical layer (Data Builder, DTO named constructor, Data Validator, ModelManager, Repository — see `@rules/laravel/architecture.mdc` Data Modification (DRY) section). Output the list of modification places explicitly in the review so the duplication picture is visible.

### Named Arguments Review
- Would positional arguments be ambiguous?
- Are there boolean, null, array, or repeated scalar values?
- Would a DTO or value object be a better design?
- Is this a public API where parameter names must remain stable?
- Are arguments still listed in the original method signature order?

### Specialized Reviews

- Always run:
    - @skills/security-review/SKILL.md
    - @skills/mysql-problem-solver/SKILL.md
    - @skills/class-refactoring/SKILL.md — read-only refactoring lens scoped to the PR diff. Use it to surface concrete tech-debt-reducing changes (DRY duplication, single-responsibility breaches, oversized methods) that apply to lines actually touched by the PR. Do not propose changes that fall outside the diff.

- Run conditionally:
    - Shared state / concurrency → @skills/race-condition-review/SKILL.md
    - I/O or external calls → I/O review

### Refactoring & Tech Debt (DRY) Analysis

Run this section over the PR diff only — never over untouched code.

1. List every block of changed lines (added or modified) per file.
2. For each block, evaluate against `@skills/class-refactoring/SKILL.md`:
   - duplicated logic that already exists elsewhere in the codebase (DRY)
   - data-shaping repeated across Actions/Services/controllers/jobs/listeners/Livewire/commands
   - oversized methods, deep nesting, mixed responsibilities introduced or amplified by the change
   - per-row DB operations in loops (link to the **Core Analysis** rule above)
3. Output the result in the **Refactoring (DRY / Tech Debt Reduction)** section of the review template:
   - file:line of the offending change
   - the duplicated/structural problem in one sentence
   - a concrete refactoring that *reduces* tech debt (consolidate into Data Builder / DTO / Service / Action / Repository per `@rules/laravel/architecture.mdc`)
4. Refactoring proposals here are improvements that fit inside the PR scope. Out-of-scope structural problems still belong in **Refactoring Proposals** (separate issue draft).

### Validation
- Verify acceptance criteria
- **Coverage gate (mandatory):** every line, branch, and condition added or modified in the current PR diff must be covered by tests.
  - Run the project coverage tool (e.g. `composer test:coverage` / `phpunit --coverage-*`) scoped to changed files.
  - Map the coverage report to the diff and list any uncovered added/changed lines as **Critical** findings.
  - If coverage tooling is unavailable or cannot be executed, raise that as a **Critical** finding instead of skipping the check.
- Identify missing test scenarios beyond raw coverage (edge cases, error paths, regressions).

---

## Output Rules

- Output only findings
- No praise, no summaries of what was checked
- Use severity levels:
    - Critical
    - Moderate
    - Minor
- Group findings by severity
- Each finding must include:
    - location
    - risk/impact
    - concrete fix
- Each **Critical** and **Moderate** finding must additionally include:
    - **Faulty Example** — minimal code snippet or input payload that reproduces the issue (redact secrets/PII)
    - **Expected Behavior** — single assertable statement (return value, exception, persisted state, emitted event)
    - **Test Hint** — one sentence pointing at the test layer (unit, integration, feature) and entry point
- These three fields exist so `@skills/process-code-review/SKILL.md` can convert each finding into a reproducer test without re-deriving context.
- Minor findings may omit these fields when no behavior change is implied (naming, dead code, etc.).

---

## Output Format

Use the template defined in `templates/review-output.md`.
---

## Principles

- Focus on risks, not style
- Prefer impact over quantity
- Avoid duplication of findings
- Prioritize regression detection
- Be precise and actionable

---

## After Completion

- Always run @skills/test-like-human/SKILL.md, regardless of code review findings.
