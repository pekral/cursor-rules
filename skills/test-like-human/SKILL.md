---
name: test-like-human
description: "Use when testing a pull request from a real user perspective. Follow PR testing instructions, simulate realistic scenarios, and produce a human-readable report."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- This skill is **stack-agnostic**. Detect the project's language, framework, and toolchain in step 1 and choose tools from that detection; the Laravel/PHP commands below are **conditional examples for Laravel projects**, not defaults. When the project IS Laravel, the Laravel-specific guidance (tinker / artisan / `APP_ENV` / `FormRequest`) stays fully in force.
- Apply the project's own conventions where they exist. When a referenced rule or skill is present in the current project, follow it: `@rules/php/core-standards.mdc`, `@rules/git/general.mdc`, `@rules/jira/general.mdc`, `@rules/reports/general.mdc`. **If a referenced `@rules/*` file or linked skill (`pr-summary`, `code-review*`) does not exist in the current project, skip it and produce the equivalent output directly** — never fail because a Laravel-specific dependency is missing.
- `@rules/reports/general.mdc` (when present): the tracker comment delegated to `@skills/pr-summary/SKILL.md` and any per-scenario annotations folded into it must be written in the language of the source assignment. The in-conversation dev-team follow-up may stay in English.
- Output must be human-readable (no technical logs or internal details)
- Focus on user-visible behavior, not implementation

## Use when
- You need to validate a pull request from a real user perspective
- You want structured testing based on PR instructions

This skill runs **on demand only** — never auto-chained from `@skills/code-review/SKILL.md`, `@skills/code-review-github/SKILL.md`, `@skills/code-review-jira/SKILL.md`, `@skills/process-code-review/SKILL.md`, or `@skills/resolve-issue/SKILL.md`. Invoke it explicitly via `/test-like-human` (or the equivalent in-conversation request) after the CR has been published, when a real user-perspective validation is genuinely wanted.

## Required approach

### 1. Understand the context
- Load the pull request (prefer `gh`, fallback to MCP tools)
- Read description, comments, and discussions
- Identify the expected final behavior
- **Detect the project type and toolchain** before choosing any tool: the primary language and framework, how the app is started/served, where env/config lives, and which REPL/console the project ships. This detection drives every tool choice below.
  - *Examples:* Laravel/PHP → `php artisan serve`, `.env`, `php artisan tinker`; Node → `npm`/`pnpm` scripts, `.env` / `process.env`, the `node` REPL; Python → `manage.py shell` / `python`, env vars; Ruby → `rails console`.
  - Universal tools stay constant across stacks: `curl` for HTTP APIs, a browser for UI. Only the backend REPL and run commands are stack-specific.

### 2. Extract testing instructions
- Locate **"Testing Recommendations"**
- Extract all scenarios
- Do not invent new requirements unless needed to verify suspicious behavior
- **Identify the gating mechanism for each scenario and record the exact toggle plus required value** needed to reach the changed branch (feature-flag name + value, ENV switch, query string, admin toggle, allow-listed account). This recorded toggle feeds both the step-3 reachability pre-check and the **Available behind** / first **How to test** step in the published report.
- **Manual reproduction is the primary output of this skill.** Mapping each scenario to automated test coverage is a **separate completeness check** that **must not** replace the manual run:
  - Map each scenario to an existing automated test; if none exists, note the gap.
  - When the assignment does **not** restrict it, write the missing test before the run is considered complete.
  - When the assignment explicitly says "test as a human / do not run automated tests", perform the manual run and **only note** the missing coverage — do not run or extend the suite.
  - Build/CI-level scenarios (e.g. `composer build`, coverage thresholds) are covered by the project's CI pipeline and need no duplicate test.

### 3. Choose testing method per scenario

**Reachability pre-check (before testing each scenario).** Confirm the changed branch is actually reachable in the local/test environment. Environment guards, feature flags, allow-listed accounts, and ENV switches can disable the change locally, so a "PASS" might exercise nothing related to the change — a false positive. *Examples of gates:* Laravel `if (App::environment('production'))`, a `config()` / feature-flag check; elsewhere `NODE_ENV`, build-time flags, `process.env.*`, LaunchDarkly. If the scenario is gated, either:
- enable the gate (flip the flag, override the ENV, use an allow-listed identity), or
- call the affected method directly with the gate forced (e.g. Laravel `php artisan tinker`; otherwise the stack's REPL).

Otherwise a "PASS" does not test anything the change touched.

Then pick the method per scenario:
- UI → browser tools
- **API → `curl` is mandatory whenever the PR changes the API** (see below); otherwise `curl` or equivalent (prefer API docs if available)
- Backend logic → the stack's REPL (Laravel: `php artisan tinker`; Node: `node`; Python: `python` / `manage.py shell`; Ruby: `rails console`)
- CLI → terminal commands

**Backend REPL / script setup.** Running backend verification through the project's REPL or a throwaway script may require:
- overriding the env so the gated branch runs (Laravel example: `APP_ENV=testing`; generally, switching the runtime environment),
- raised limits for heavy operations (Laravel/PHP example: `memory_limit`; generally, memory/time limits),
- a local substitute for production infrastructure that does not run locally (managed DB, cloud SDK, queues).

Use throwaway data and clean up afterwards: delete the artifacts and restore the branch / working tree to its original state.

**API changes → mandatory `curl` verification.** If the PR changes the API — route/endpoint definitions, controllers/handlers, the validation layer, response/serialization, or status codes (Laravel examples: `routes/api.php`, API controllers, `FormRequest`, API Resources) — `curl` verification is **required**, not optional:
- For each changed/added endpoint, issue a real `curl` request against the local environment with the correct HTTP method, authentication (token / API key), headers, and body.
- Verify the contract: **status code**, response shape and types, validation errors on invalid input, and authorization (no token / foreign token); check idempotency where it applies.
- Cover the happy path **and** edge/negative cases (missing required field, unauthorized access) — not just `200`.
- If public API docs exist, verify against them and **flag any mismatch** between code and docs (code is the source of truth).

Do not over-test — focus on meaningful validation.

### 4. Execute as a senior tester
For each scenario, think:
- what the user tries to achieve
- where the flow could fail or confuse
- whether behavior feels correct and trustworthy
- for backend changes: whether data ends in the correct state

### 5. Validate results
- Compare expected vs actual behavior
- **Confirm the observed behavior was caused by the changed code**, not an unrelated branch or an environment that skips the change. Where it makes sense, verify the triple:
  - positive — the fix works,
  - negative — a different error / input does not behave like the handled case and does not pass silently,
  - legacy preservation — behavior outside the gate stays unchanged.
- Identify inconsistencies, confusion, or broken flows
- Do not expose technical details in conclusions

## Report format

Local in-conversation report only — use the template defined in `templates/test-report.md` for the agent's own working notes (raw scenario results, observations, blockers). This template **must not** be posted to any tracker.

## Deliver
- Reference the pull request
- Include all tested scenarios
- Provide overall summary
- Highlight failed / blocked / unclear cases
- Recommend whether the change is ready from a user perspective

## After completion

The tracker-facing output is **produced by `@skills/pr-summary/SKILL.md` when it exists in the project**. This skill does not author its own JIRA / GitHub comment template — that responsibility belongs to `pr-summary`, which already enforces the uniform *Authors / Available behind / Summary of changes / How to test* contract. **When `pr-summary` is not present in the current project, produce the equivalent tracker comment directly, following the same contract** — do not skip the report and do not fail on the missing dependency.

1. Hand the raw test-report markdown (from `templates/test-report.md`) and the per-scenario results to `@skills/pr-summary/SKILL.md` as input context for the publishing step (or, when absent, to your direct report).
2. Invoke `pr-summary` with the target tracker matching the PR origin (GitHub for GitHub PRs, JIRA for JIRA-tracked work).
3. The published tracker comment **must**:
   - credit the **real change author(s)** in the `Authors` line — resolved from git history and PR metadata, never the agent / tester identity running this skill;
   - include the **Available behind** line whenever the verified change is reachable only behind a test parameter (feature flag, ENV switch, query string, admin toggle, allow-listed account) — pass the gating toggle and required value recorded in step 2 so its first **How to test** step enables it;
   - in the **How to test** section, fold the test scenarios actually executed by this skill (including pass / fail / blocked / unclear status next to each step, and the `curl` request + key contract checks for every API endpoint touched), so the published comment reflects real verification work rather than restating the PR description.
4. Append a short non-public follow-up message to the dev team (in conversation, not on the tracker) listing failed / blocked / unclear scenarios with enough technical detail to act on them. That message is for the developers — it complements the `pr-summary` tracker comment, it does not replace it.
