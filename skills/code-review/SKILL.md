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
- **Read-only skill** — never modify code, never stage / commit / push changes, and never run any git write operation (`git add`, `git commit`, `git push`, `git reset`, `git checkout -- …`, etc.). Switching to the relevant branch and `git pull` to read the latest diff are allowed; mutating the working tree or pushing to the remote is not. Output is the review markdown only.
- Apply @rules/reports/general.mdc — the review markdown handed to `code-review-github` / `code-review-jira` for publishing on the **GitHub PR** stays in canonical English per the rule's *Exception — technical CR findings on the GitHub PR* (severity labels, structured field labels, rule references, and code identifiers are all in English). The non-technical mirror that the wrappers delegate to `@skills/pr-summary/SKILL.md` follows the language of the source assignment — that is the wrapper's responsibility, not this skill's.
- Do not duplicate findings the project's fixers already auto-correct (Pint, PHPCS, Rector — pure whitespace, import ordering, unused-use, single-line vs multi-line argument splits). Those are caught by the build. **Do** flag every rule violation a fixer does not cover — architectural breaches, structural rules, missing return types, untyped DTO boundaries, naming bound to a domain rule, testing-pattern violations, etc.

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

1. Load the complete issue or task (description, all comments, and attachments) from the linked tracker (GitHub, JIRA, Bugsnag). For JIRA issues, call `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>` and read all fields off the resulting JSON document — never call `acli` directly. Fall back to the JIRA MCP server only when the script is unavailable or for data outside its scope (changelog, available transitions, friendly custom-field names).
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
- Reuse of existing logic — for every block of newly added or modified logic in the diff, search the codebase for an existing implementation that already does the same thing (helper, Service, Action, Data Builder, DTO, trait, ModelManager, Repository, scope, etc.). If equivalent logic already exists, flag the change and require reusing it instead of introducing a parallel implementation. The goal is unified logic across the application; parallel implementations of the same behavior are a finding (see `@rules/code-review/general.mdc` Reuse Existing Logic section).
- Speculative interfaces — flag any new project-owned `interface` that has neither at least two non-test consumers nor at least two non-test implementations. Test doubles, mocks, and fakes do not count toward either threshold. Implementing a framework or vendor interface (e.g. `ShouldQueue`, `HasLabel`, `Arrayable`) is always allowed. Require collapsing single-implementation, single-consumer interfaces back into the concrete class unless the PR documents an architectural reason — a published package API surface or a plugin extension point with a written contract — see `@rules/php/core-standards.mdc` Design Principles.
- **Strict rule compliance (mandatory walk-through)** — for every rule file applied via Constraints (`@rules/php/core-standards.mdc`, `@rules/code-review/general.mdc`, `@rules/refactoring/general.mdc`, `@rules/code-testing/general.mdc`, and on Laravel projects `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, `@rules/laravel/livewire.mdc`, plus any project-specific `@rules/**/*.mdc`), scan the diff for any pattern that matches a numbered or bulleted rule from those files and raise one finding per matched violation. The standard is "every applicable rule must hold on every changed line", but the review process is pattern-matching the diff against the rule set, not asserting each rule against each line individually. Each finding cites `file:line` **and** the rule reference (e.g. `@rules/php/core-standards.mdc#PHP Practices` or `@rules/laravel/architecture.mdc#Business Logic Layers`). Severity defaults — apply the severity declared in the rule file's CR Severity Rules section if present; otherwise: architectural / structural / required-pattern violations are **Critical**, PHP-practice violations a fixer doesn't catch (missing return types, raw arrays across boundaries when DTOs exist, magic numbers, unsuppressed errors, generic exceptions, untyped iterables) are **Moderate**, naming or wording nits without a binding rule are **Minor**. Do not summarize "all rules apply" as a single finding — each violation needs its own line so the reviewer can verify the citation.
- **Architecture conformance (Laravel)** — section-by-section deep-dive for `@rules/laravel/architecture.mdc` referenced by Strict rule compliance above. Count each finding **once**; cite this bullet when the violation maps to a specific architecture section, otherwise cite Strict rule compliance. When `@rules/laravel/architecture.mdc` is in scope, walk every section of that file against the diff: **Architecture**, **Business Logic Layers** (seven allowed homes including the Eloquent-model carve-out), **Actions** (orchestration-only, single `__invoke()`, `final readonly`, constructor injection), **Action Rules** (no inline Eloquent, no `DB::`, no inline persistence), **Model Services** (`BaseModelService` extension, no inline queries), **Repositories and ModelManagers** (read/write separation, basic queries only, batch-first writes), **DTOs** (typed boundaries, mapping attributes, no raw arrays across layers), **Data Modification (DRY)**, **Data Builders** (multi-method, no DB), **Validation Rules (Traits)**, **Data Validators** (`DataValidator` trait when `pekral/arch-app-services` is installed), **Controllers and Other Entry Points** (slim, FormRequest, no inline `validate()`), **Resource Controllers** (CRUD-only), **Single-Action Controllers**, **Livewire** (entry point, `boot()` injection, no business logic), **Custom Helpers** (global `app/helpers.php` functions, no static-method wrappers), and the **CR Severity Rules** subsection. Inherit the severity declared in CR Severity Rules; absent that, default to **Critical** for any orchestration / persistence / query bypass and any new code outside the seven allowed business-logic layers.
- Per-row DB operations in loops — flag any loop that issues per-row `update()`, `create()`, `delete()`, or single-row read; require batching via ModelManager `batchUpdate` / `batchInsert`, `whereIn(...)->delete()`, or a single bulk read keyed in memory (see `@rules/sql/optimalize.mdc` "Batch over per-row operations"). Allowed only when an explicit code comment or PR note justifies an unavoidable per-row side-effect dependency.
- SQL query reuse of existing indexes — for every new or modified SQL / Eloquent / query-builder code in the diff, locate the current DB schema (migrations under `database/migrations/`, model `$table` metadata, and live `SHOW INDEX` output when DB access is available) and verify the query is shaped to hit an existing index. Flag queries that bypass an existing covering index — `WHERE` / `JOIN` / `ORDER BY` column order does not match the left-to-right composite order, functions wrap indexed columns (non-SARGable), or `SELECT` pulls columns outside the covering index. The **Suggested Fix** must rewrite the query (column re-ordering, SARGable rewrite, covering projection) — propose a new index only when no existing index can cover the query and the schema gap is justified by EXPLAIN. Severity: **Moderate**, escalated to **Critical** when the un-indexed query runs on a hot path or large table (see `@rules/sql/optimalize.mdc` "Reuse existing indexes first").
- Third-party API/service contract — when changes touch external APIs or services, verify the implementation matches the public API documentation, satisfies the issue assignment, and covers all relevant in-scope API use cases (see **Third-Party API & Service Analysis** section)
- Refactoring quality — when changes are refactoring in nature, validate them against `@rules/refactoring/general.mdc`: behavior must be preserved, migration must be incremental (no big-bang rewrites), and entry points / responsibilities / DRY / concurrency must follow the recommended process. In Laravel projects, combine with `@rules/laravel/architecture.mdc`.
- Data validation encapsulation — verify that all validation logic is in dedicated Data Validator classes or FormRequests (using validation rules from reusable traits in `app/Concerns/`), not inline in Actions, controllers, jobs, commands, listeners, or Livewire components (see `@rules/laravel/architecture.mdc` Data Validators section)
- Repository scope — verify Repositories expose only basic, reusable queries (`find`, `findBy{Attribute}`, `all`, simple `where` lookups, pagination of a base scope). Feature-specific or use-case–specific query methods in Repositories are a finding; specialization belongs in a Service (single-model) or Action (cross-model / cross-feature) composing basic Repository methods (see `@rules/laravel/architecture.mdc` Repositories and ModelManagers section)
- Data Modification (DRY) — enumerate every place in the changes that modifies data before it is saved or passed downstream (DTO mapping, payload shaping, key renaming, default fallbacks, format normalization, business-driven derivation). Cross-check against existing entry points; if the same shaping appears in more than one Action / Service / controller / job / listener / Livewire component / command, flag it and require consolidation into the canonical layer (Data Builder, DTO named constructor, Data Validator, ModelManager, Repository — see `@rules/laravel/architecture.mdc` Data Modification (DRY) section). Output the list of modification places explicitly in the review so the duplication picture is visible.

### Highest-Priority Fast Track

Apply this subsection only when the source issue is flagged as **highest priority**, so the bug fix can deploy as fast as possible without sacrificing the Critical / Moderate gate.

1. **Detect highest priority** from the issue context already loaded under **Issue Context Analysis**:
   - **GitHub:** any label whose name matches (case-insensitively) `priority: highest`, `priority/highest`, `priority-highest`, `p0`, `urgent`, or `blocker`.
   - **JIRA:** the native `priority` field equals `Highest` or `Blocker`.
   - **Bugsnag:** the linked GitHub issue carries one of the GitHub labels above.
   If no signal matches, skip the rest of this subsection and run the review normally.
2. **Narrow the review scope** to whatever directly affects the bug fix and its safe deployment. Out-of-scope improvements that the diff merely happens to sit near must be moved to **Refactoring Proposals** as follow-up items, never blockers.
3. **Keep the resolution gate at Critical and Moderate.** No widening, no narrowing — those two severities still block the merge, exactly as in the default flow. State this explicitly in the review header so the caller does not have to infer it.
4. **Demote non-blocking sections to follow-up only.** Still emit them so nothing is lost, but mark each entry as *follow-up; does not block merge*:
   - **Minor** findings (naming, dead code, wording nits without a binding rule).
   - **Refactoring & Tech Debt (DRY) Analysis** entries that propose changes beyond the literal bug fix.
   - **Refactoring Proposals** drafted for separate issues.
   Critical and Moderate findings, the **Strict rule compliance** walk-through, the **Coverage gate**, the **Database Analysis** section, and every **Specialized Review** that the diff triggers stay mandatory and blocking — fast-track never skips them.
5. **Record the fast-track decision** in the review output: the matched signal (label name or JIRA priority value), the deferred sections, and a one-line reminder that the gate remained Critical + Moderate.

### Named Arguments Review
- Would positional arguments be ambiguous?
- Are there boolean, null, array, or repeated scalar values?
- Would a DTO or value object be a better design?
- Is this a public API where parameter names must remain stable?
- Are arguments still listed in the original method signature order?

### Specialized Reviews

- Always run:
    - @skills/prepare-issue-context/SKILL.md with `MODE=cr` **as a pre-flight before any other specialized review** — audits whether the dev database currently holds the fixture data the assignment scenarios need, and surfaces every scenario the diff *should* cover but the codebase / dev DB cannot reproduce. The CR uses its gap report to ground the rest of the review in real data; an empty gap report means later findings (assignment compliance, security, refactoring) are made against scenarios that genuinely exist, not against guesses. Surface the gap count in the PR comment summary line and treat any **behavioral gap** the skill reports as a **Critical** CR finding (the diff cannot satisfy a scenario the codebase does not support).
    - @skills/assignment-compliance-check/SKILL.md — verifies the PR implementation satisfies the business requirements stated in the linked issue / task. The skill publishes the **Assignment Compliance** report as a dedicated comment on the originating issue tracker (GitHub issue or JIRA ticket) and returns only a status string to the caller (`posted to issue #N` / `posted to <KEY>`, `no linked issue — assignment compliance skipped`, or `failed to publish …`). **Do not embed** the report into the PR comment — that comment carries technical findings only. Surface the returned status in the PR comment summary line.
    - @skills/security-review/SKILL.md
    - @skills/class-refactoring/SKILL.md — read-only refactoring lens scoped to the PR diff. Use it to surface concrete tech-debt-reducing changes (DRY duplication, single-responsibility breaches, oversized methods) that apply to lines actually touched by the PR. Do not propose changes that fall outside the diff.

- Run conditionally:
    - **Database operations detected in the diff → `@skills/mysql-problem-solver/SKILL.md` is mandatory.** Trigger this skill whenever the diff touches any of: raw SQL strings, Eloquent / query-builder calls (`DB::`, `->where(`, `->join(`, `->whereHas(`, `->withCount(`, `->orderBy(`, `->groupBy(`, `->chunk(`, `->cursor(`, `paginate(`, `simplePaginate(`), Eloquent relationship definitions, `with(` / `load(` eager loads, model scopes, ModelManager / Repository methods, database migrations (`Schema::`, `up()` / `down()`), seeders, factories that materialise rows, DynamoDB / NoSQL access. Pass the diff scope to `mysql-problem-solver` and capture its findings — they **must** appear in the published CR review under the dedicated `## Database Analysis` section described in **Output Rules**, never silently absorbed into the generic Critical / Moderate / Minor buckets.
    - Shared state / concurrency → @skills/race-condition-review/SKILL.md
    - I/O or external calls → I/O review

### Refactoring & Tech Debt (DRY) Analysis

Run this section over the PR diff only — never over untouched code.

1. List every block of changed lines (added or modified) per file.
2. For each block, evaluate against `@skills/class-refactoring/SKILL.md`:
   - duplicated logic that already exists elsewhere in the codebase — diff-scoped pass of Core Analysis "Reuse of existing logic" (DRY); do not restate the rule, raise the finding once
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
- **Coverage gate (mandatory, diff-scoped only):** every line, branch, and condition added or modified in the current PR diff must be covered by tests. The CR coverage step **must execute the diff-scoped script alone** — never the project-wide coverage command (`composer test:coverage`, `composer coverage`, full Phing `coverage` target, `pest --coverage --min=100` on the whole suite, etc.).
  - Discover the diff-scoped command in this order: (1) **`vendor/bin/test-coverage-diff`** — composer-installed binary shipped by `pekral/cursor-rules` itself; auto-picks PCOV → Xdebug, auto-detects Laravel projects (`app/` + `src/`) vs non-framework projects (`src/`), and works in any consuming project without requiring a per-project Composer script entry; (2) Phing target named `test:coverage:diff` / `coverage:diff` in `build.xml` / `phing.xml`; (3) Composer script `test:coverage:diff` in `composer.json` (this repository's own `composer test:coverage:diff` delegates to the same `bin/test-coverage-diff`); (4) any project-specific script whose name contains `diff` and `coverage`. Do not assume a default, do not fall back to a full-suite coverage command, and **do not create a new `composer test:coverage:diff` script** in the consuming project — the binary from step (1) is the canonical entry point.
  - Run that script exactly once. It is responsible for: scoping coverage to lines changed vs the base ref, selecting the available coverage driver (PCOV → Xdebug), and reporting uncovered changed lines.
  - Map the script's report to the diff and list any uncovered added/changed lines as **Critical** findings.
  - If no diff-scoped coverage script exists, or it cannot be executed (missing driver, missing Pest binary, no source directory), raise that as a **Critical** finding instead of skipping the check or substituting the full-suite command.
  - Always include a `## Coverage` section in the published review reporting the discovered diff-scoped script, the exact command run, and the result for changed lines (or "diff-scoped tooling unavailable" with reason). Never finalize a review without it, and never replace this section with full-project coverage output.
- Identify missing test scenarios beyond raw coverage (edge cases, error paths, regressions).

---

## Output Rules

- Output only findings
- No praise, no summaries of what was checked
- **Omit empty sections entirely.** Only the header block (Status / Counts / Coverage / tracker-status line), the `## Coverage` section, and the final `Summary` line are always rendered. Every other section — `Previous CR Status`, `Findings` (including each severity sub-heading), `Refactoring (DRY / tech debt)`, `Refactoring proposals`, `Database Analysis`, and any specialized-review sub-section — appears **only when it has at least one item**. Never emit `None.` / `Not applicable.` / `n/a` placeholders for empty sections; drop the whole heading and body instead. The Counts line in the header is the single source of "zero" signal so a clean review stays scannable.
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
    - **Suggested Fix** — minimal corrected code snippet that resolves the finding. Must comply with `@rules/php/core-standards.mdc` and, for Laravel projects, `@rules/laravel/architecture.mdc`. Use `n/a — <reason>` only when a snippet adds no value over the one-line Fix description (e.g. naming-only changes, dead-code removal, pointers to an existing helper whose name already says enough).
- These four fields exist so `@skills/process-code-review/SKILL.md` can convert each finding into a reproducer test and apply the fix without re-deriving context.
- Minor findings may omit these fields when no behavior change is implied (naming, dead code, etc.).
- This skill is read-only and does not publish anywhere itself. The wrapper skills that consume its output (`@skills/code-review-github/SKILL.md`, `@skills/code-review-jira/SKILL.md`) **must** delegate the non-technical summary on **every linked issue** in the originating tracker (GitHub issue, JIRA ticket, or both) to `@skills/pr-summary/SKILL.md` — the CR wrappers never author their own non-technical template. `pr-summary` produces a uniform *"Summary of changes + How to test"* comment understandable by non-technical project managers, rendered as GitHub Markdown for GitHub issues and JIRA Wiki Markup for JIRA tickets per `@rules/jira/general.mdc`. Technical findings still go directly on the PR comment.
- **Translation completeness (mandatory when the project ships translations):** detect whether the project uses translations by looking for any of: a `lang/` directory (Laravel), a `resources/lang/` directory (older Laravel layout), a `translations/` directory (Symfony), `*.json` / `*.po` / `*.mo` locale files under a recognised locale directory, calls to `__()` / `trans()` / `Lang::get()` / `@lang` / `t()` / `i18next.t()` / `useTranslation()` / `$t(` / `$tc(` anywhere in the diff or in the wider project. **If none of those signals match, skip this section entirely.** When translations are in use, for every user-facing string introduced or modified by the diff (including labels in Blade / Livewire / Filament / Vue / React templates, validation messages, Notification subject and body, Mailable / Markdown views, FormRequest custom `messages()`, enum `getLabel()`, Filament resource / form field labels, log messages exposed to humans, exception messages surfaced to users) walk every detected locale and raise one finding per missing key — that is, the diff added a translation key in the default locale but did not add (or updated but did not synchronise) the same key in every other locale shipped with the project, **or** the diff hard-codes a literal string in a user-facing surface even though the surrounding code uses `__()` / `t()` for analogous strings. Severity: **Moderate**; escalate to **Critical** when the missing key is on a primary-flow surface (login, checkout, primary CRUD form, transactional email) or when the project's CI / build asserts translation parity and the diff would break that gate. Each finding cites the missing locale and key, and the **Suggested Fix** is the literal translation entry the locale file is missing (translated into the locale's language; for languages the agent cannot translate confidently, leave the value as `TODO(<locale>): translate "<source>"` and flag the gap explicitly so a human translator picks it up).
- **Database Analysis section (mandatory when the diff touches DB operations):** when the conditional `@skills/mysql-problem-solver/SKILL.md` trigger fires (see **Specialized Reviews** for the trigger pattern list), append a dedicated `## Database Analysis` section to the published review **before** the `## Coverage` section. The section reports only the findings — each with severity on the **Critical / Moderate / Minor** scale and the proposed query rewrite / index reuse / batching fix per `@rules/sql/optimalize.mdc`. Do not include the trigger decision, the inspected `file:line` list, or the EXPLAIN / static-analysis summary; those belong to the internal `mysql-problem-solver` investigation, not to the published review. When no DB operations are present in the diff, omit the section entirely; never replace it with a generic "no DB changes" placeholder absorbed into Coverage or the summary line.
- **Default severity for rule violations:** every unexcused violation of an Apply'd rule on a line touched by the diff defaults to the severity declared in that rule file's CR Severity Rules subsection if present. Otherwise apply the **Strict rule compliance** stratification from Core Analysis: architectural / structural / required-pattern violations are **Critical**; PHP-practice violations a fixer doesn't catch are **Moderate**; naming / wording nits without a binding rule are **Minor**. Reviewers may not silently downgrade further than that stratification; if a rule's spirit is satisfied by an alternative the diff documents in code or PR description, cite that exemption explicitly in the finding instead of suppressing it.

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

- Do **not** auto-invoke `@skills/test-like-human/SKILL.md`. The user-perspective testing skill runs **on demand only** (via `/test-like-human` or an explicit follow-up); CR-track skills must never chain into it.
