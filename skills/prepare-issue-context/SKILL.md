---
name: prepare-issue-context
description: "Use when preparing data and context before /resolve-issue, TDD, or CR runs. Loads the assignment, extracts every concrete user scenario from the task description and acceptance criteria, maps each scenario to the codebase, seeds the development database with the records needed to reproduce the bug or feature end-to-end, and reports any gap that would force the implementing agent to hallucinate."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Prepare Issue Context

## Purpose
De-risk the next implementation step (`/resolve-issue`, TDD, or CR) by **front-loading the data and codebase context** the agent needs to act without hallucinating. The skill ends with one of two states: **ready** (the development database holds every record the assignment scenarios refer to, and every scenario is mapped to a concrete code path) or **blocked** (a gap exists — the calling skill must stop and surface the gap to the user instead of guessing).

---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Apply `@rules/jira/general.mdc` when the assignment lives in JIRA
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- **Read-only for production code.** This skill never modifies production source files. It is allowed to: create temporary seeders / factories / Tinker scripts under `database/seeders/`, `database/factories/`, or a scratch directory; insert rows into the **development** database; create scratch Pest tests that reproduce the bug. It is **never** allowed to mutate the production database, run destructive migrations, drop tables, push to the remote, or modify code in `src/` / `app/` outside of seed-only fixtures.
- Never invent values that are not derivable from the assignment, the codebase, or the existing dev database. When a required value (account ID, contact phone, enum case, config key) cannot be resolved, list it as a gap — do not guess.
- Never expose secrets, production credentials, or PII when seeding. Use test fixtures (`+420604240203`-style sentinels, `qa-*` aliases) explicitly tagged as such.
- Apply `@rules/reports/general.mdc` — when a context-preparation summary is published to the tracker (via `@skills/pr-summary/SKILL.md`), it must be written in the language of the source assignment. The in-conversation `ready` / `blocked` status is allowed to stay in English.

---

## Use when
- The user invokes `/resolve-issue`, `/test-driven-development`, or any skill that wraps them, and the assignment is non-trivial (multi-scenario, references states / branches / external data).
- A CR (`/code-review`, `/code-review-github`, `/code-review-jira`) is about to start and the reviewer needs to confirm the PR satisfies real assignment scenarios — the CR uses this skill as a **pre-flight** to verify the developer working set contains enough fixture data to actually reproduce the scenarios named in the assignment, instead of accepting the diff on its face.
- The agent is about to write a failing test (TDD RED) and needs the real-world fixture state in front of it so the test is not a stub.

Do **not** run this skill for tasks that are obviously stateless (formatting, dependency bumps, docs-only changes).

---

## Inputs
- `ASSIGNMENT_REF` — required. Issue / JIRA / Bugsnag URL or ID. The same identifier `/resolve-issue` would accept.
- `MODE` — optional. One of `resolve-issue` (default), `tdd`, `cr`. Selects the depth of seeding (see step 5).
- `SCOPE` — optional. When the caller already knows which scenarios are in scope, pass the scenario numbers / titles to skip the extraction step.

---

## Required approach

### 1. Load the assignment
- Detect the originating tracker (GitHub, JIRA, Bugsnag) using `@skills/resolve-issue/references/source-detection.md`.
- Load the issue via the deterministic loader — `skills/code-review-github/scripts/load-issue.sh` or `skills/code-review-jira/scripts/load-issue.sh`. For JIRA, prefer `skills/code-review-jira/scripts/gather-issue-context.sh <KEY|URL>` to pull the whole context in one pass (issue + comments + attachments + recursively-loaded linked issues + an inventory of external URLs). Never call `gh`, `acli`, or REST endpoints directly.
- Read the full `body` / `descriptionText`, every entry in `comments[]`, every attachment URL, and the linked PRs.
- Group comments by thread per `@skills/resolve-issue/SKILL.md` *Comment analysis* — keep only the **current** requirements.

### 2. Extract concrete scenarios
For every numbered step, bullet, or paragraph in the *Jak otestovat* / *How to test* / acceptance-criteria section of the assignment, record one scenario:

- **Scenario title** — short label (3–8 words).
- **Trigger** — the user action that starts the scenario (UI click path, API call, CLI command, queued job).
- **Inputs** — every literal value the assignment names: dates, phone numbers, link fragments, UI fields, search strings, account aliases, enum cases. Quote them verbatim — do **not** rewrite, normalize, or translate them.
- **Expected outcome** — the observable result the assignment expects (filter result includes / excludes a record, status flips, email arrives, dropdown lists specific items, export field is empty after import).
- **Edge constraints** — boundary conditions called out by the assignment (first / last day of a window, partial match, empty results, large dataset, account isolation).

Output the list as a numbered table the rest of the steps refer to.

### 3. Map every scenario to the codebase
For each scenario from step 2, locate the concrete code path that owns the behavior:

- **Entry points** — controller / Livewire / job / command / listener / Filament page that the trigger lands on.
- **Business logic** — Action / Service / Repository / ModelManager / Data Validator / Data Builder per `@rules/laravel/architecture.mdc` (or the project-equivalent layer) called from the entry point.
- **Persistence shape** — Eloquent models, tables, columns, indexes, enum cases, and pivot rows the scenario reads or writes.
- **Boundary integrations** — external APIs, queues, signed-URL endpoints, mail templates, SMS providers the scenario touches.

When the mapping cannot be made (no matching entry point in the codebase, ambiguous between two services, named entity does not exist), record the scenario as a **mapping gap** — see step 6.

### 4. Build the data inventory
For every scenario that mapped cleanly in step 3, enumerate the records that must exist in the development database before the scenario can be exercised:

- the account / tenant / workspace the scenario lives under (use the test alias from the assignment when one exists, e.g. `qa-cz-1`);
- every entity the scenario reads or filters on, with the exact column values from step 2 (status enum cases, dates inside the boundary window, link fragments, contact identifiers);
- adjacent records needed only to make the scenario observable (e.g. *one contact whose log contains a click event, one contact whose log contains only an open event, one contact with no events* — when the scenario distinguishes outcomes between these three);
- timing-sensitive fields (`created_at`, `sent_at`, `delivered_at`) set explicitly inside / outside the boundary window — never *now*, never a random factory value.

Group the inventory by table; deduplicate records shared across scenarios.

### 5. Seed the development database
Run only against the **development** database — never staging, never production.

- **Discover the seeding entry point** the project already uses, in this order: project `database/seeders/` Laravel seeders, model factories under `database/factories/`, custom Tinker scripts referenced from `README.md` / `CLAUDE.md`, fixture loaders for non-Laravel projects (Doctrine fixtures, scratch SQL files). Prefer the project convention; do not introduce a new mechanism unless none exists.
- **Write one scratch seeder per scenario group from step 4**, named `Prepare<IssueKey>ContextSeeder` (Laravel) or the project equivalent. Keep them under a scratch namespace (e.g. `database/seeders/Scratch/`) so the caller can delete them after the work is done.
- Execute the seeders against the dev database and confirm each insert / update succeeded (count rows after the seed; abort if any insert returned zero affected rows).
- **MODE depth:**
  - `resolve-issue` (default) — seed every scenario inventory.
  - `tdd` — seed only the scenarios the caller will use for the RED test (passed via `SCOPE`); skip the rest so the failing test stays focused.
  - `cr` — **do not insert anything**; instead, run the queries the assignment scenarios depend on against the existing dev database and record which scenarios already have realistic data and which would require a new seed. The CR caller decides whether to proceed.

### 6. Run gap analysis
Combine everything from steps 2–5 into a single gap list:

- **Mapping gaps** — scenarios with no clear code-path owner.
- **Data gaps** — scenarios whose inventory could not be fully seeded (missing config key, missing enum case, missing parent entity, integration that the dev environment cannot reach).
- **Behavioral gaps** — scenarios that name a behavior that the current codebase cannot produce (e.g. a filter the assignment refers to does not exist; an event the assignment expects is never dispatched). These are findings the calling skill must surface to the user; they often mean the assignment itself is incomplete.
- **External dependency gaps** — scenarios that require live calls to a third-party API or a service that is unavailable in dev (Stripe, SendGrid, GoSMS, SQS). For these, record the contract the implementation will need to mock or stub instead.

Every gap entry must include: the scenario number, what is missing, the exact source of the missing piece (assignment line / code path / table column), and whether the caller can proceed without it.

### 7. Verify reproducibility (when MODE is `resolve-issue` or `tdd`)
For each scenario without gaps, run a one-shot reproduction against the seeded dev database:

- Bug scenarios — confirm the bug behavior is observable in the current codebase (run the trigger, observe the wrong outcome). If the bug does **not** reproduce after seeding, flag it as a **reproducibility gap** (the assignment description, the seed plan, or the codebase mapping is wrong — do not let the implementing agent guess).
- Feature scenarios — confirm the current outcome (what happens before the feature is implemented) so the TDD RED step has a baseline.

Capture the reproduction step verbatim — entry point, inputs, observed output. The implementing skill will reuse it for the failing test.

### 8. Publish the report
Two destinations:

- **In conversation (every run):** a short status — `ready` or `blocked: <count> open gap(s)`. When `blocked`, list the gaps as a bulleted checklist so the caller can stop immediately.
- **On the originating tracker (only when MODE = `resolve-issue` and at least one gap was filled or one scenario mapped non-trivially):** delegate the comment to `@skills/pr-summary/SKILL.md`. The published shape follows the target tracker: on **GitHub** the comment carries the full *Authors / Available behind / Summary of changes / How to test* contract; on **JIRA** `pr-summary` renders **only How to test** (plus any conditional embedded blocks), so fold the dev-DB state the testers need into the test steps themselves — the JIRA comment does not carry an *Authors* line, a *Summary of changes* section, or a standalone scenario / gap table. Keep the scenario / gap table and seed plan in the in-conversation report regardless of target. The non-technical comment never contains code identifiers, file paths, or seed-class names — those stay in the in-conversation report.

---

## Integration with other skills

- **`@skills/resolve-issue/SKILL.md`** — invoke this skill as a pre-flight between *Required approach → Comment analysis* and *Problem analysis*. The caller must abort the resolve flow when the status is `blocked` and surface the gaps to the user.
- **`@skills/test-driven-development/SKILL.md`** — invoke this skill before the first RED step. The seeded dev database becomes the source of truth for the failing test; the reproduction record from step 7 becomes the test's *arrange* block.
- **`@skills/code-review/SKILL.md`** / `code-review-github` / `code-review-jira` — invoke this skill with `MODE=cr` as part of *Specialized Reviews* (alongside `assignment-compliance-check`). The CR uses the gap report to validate that the PR actually addresses the real assignment scenarios — not just the diff in isolation. CR mode never seeds; it audits.
- **`@skills/assignment-compliance-check/SKILL.md`** — consumes the scenario table from step 2 directly. When this skill runs before compliance-check, the latter reuses the mapping in step 3 instead of re-deriving it.

---

## Done when
- The assignment has been loaded via the deterministic loader.
- Every scenario from the assignment's *Jak otestovat* / acceptance-criteria section has a numbered entry with trigger, inputs, expected outcome, and edge constraints.
- Every scenario is either mapped to a concrete code path or recorded as a mapping gap.
- For each mapped scenario, the development database holds the records the scenario depends on (or, in `cr` mode, the query results were captured).
- A bug scenario has been reproduced against the seeded dev database, or is recorded as a reproducibility gap.
- The in-conversation status is `ready` or `blocked: <count> open gap(s)` with the full gap list.
- When `MODE=resolve-issue` and the caller proceeded, the non-technical tracker comment was delegated to `@skills/pr-summary/SKILL.md` and posted on the originating tracker.

---

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
