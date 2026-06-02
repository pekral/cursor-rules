# Code Review

> **Section visibility — render only sections that have content.** Always render the header block (Status / Counts / Last updated) and the final `Summary` line. The `Coverage:` header line, the `## Coverage` section, and the `coverage …` slot in the summary line are conditional — render them **only** when the coverage gate produced something to report (uncovered changed lines or unavailable / non-runnable tooling, both Critical findings per `@skills/code-review/SKILL.md` Coverage gate). When every changed line is at 100% coverage and the tool ran successfully, drop all three coverage surfaces; the Counts line is the clean signal. The `## Architecture` section is the second exception (issue #530): on Laravel projects render the heading on every CR run — with the listed findings when any exist, or with the literal status line `walked, 0 findings` when the walk is clean — so the reviewer can see the check ran. On non-Laravel projects (`laravel/framework` not in `composer.json` `require`), omit the `## Architecture` section entirely. Every other section is conditional: omit its heading and body entirely when it has no items. Never emit `None.` / `Not applicable.` / `n/a` / `100%` placeholders for empty sections or omitted coverage surfaces — drop them entirely. The Counts line in the header is the single source of "zero" signal; the goal is a clean, scannable PR comment a human can read at a glance.

**Status:** clean / needs-fix
**Counts:** Critical {n} · Moderate {n} · Minor {n} · Refactoring {n}
**Coverage:** {result} (tool: {name or "not available — <reason>"})  *(render this line only when the `## Coverage` section is rendered — i.e. uncovered changed lines or unavailable tooling)*
**Last updated:** {ISO-8601 timestamp of this CR run}

> **Single-comment upsert:** the CR wrapper (`code-review-github` / `code-review-jira`) publishes this output as **one comment per (PR | linked issue | JIRA ticket, actor)** keyed by an actor marker (`<!-- cr-comment:actor=<gh-login> -->` for GitHub, `{anchor:cr-comment-actor-<slug>}` for JIRA). Follow-up CR runs **edit that comment in place**, so history is preserved by the tracker's edit history — never re-create a `Previous CR Status` section in the body.

---

## Findings

> Render only when at least one Critical, Moderate, or Minor finding exists. Within this section, render only the severity sub-headings that have items — omit the others entirely. When all three severities are empty, omit the entire `## Findings` parent heading.

### 🔴 Critical 1. <short title>

- **Location:** `path/to/file.php:42`
- **Rule:** `@rules/<area>/<file>.mdc#<section>`
- **Impact:** one sentence — what breaks or what risk this introduces.
- **Faulty Example:**
  ```php
  // minimal code or input that reproduces the issue (no secrets / PII)
  ```
- **Expected behavior:** single assertable statement (return value, thrown exception, persisted state, emitted event).
- **Test hint:** test layer (unit / integration / feature) + entry point, in one sentence.
- **Suggested fix:**
  ```php
  // minimal corrected snippet — must comply with @rules/php/core-standards.mdc (and @rules/laravel/architecture.mdc on Laravel projects). Use `n/a — <reason>` only when a snippet adds no value.
  ```

### 🟠 Moderate 1. <short title>

(same six fields as Critical)

### 🟡 Minor 1. <short title>

- **Location:** `path/to/file.php:42`
- **Note:** one sentence. Faulty Example / Expected behavior / Test hint / Suggested fix may be omitted when no behavior change is implied.

---

## Refactoring (DRY / tech debt)

> Render only when at least one in-scope refactoring item exists. Only items on lines touched by this PR (added or modified). Each item must reduce tech debt — no stylistic preferences. Omit the entire section when there are no items.

1. **Location:** `path/to/file.php:42`
   **Problem:** one sentence.
   **Refactor:** concrete consolidation step (Data Builder / DTO / Service / Action / Repository / ModelManager).
   **Why:** rule reference (`@rules/laravel/architecture.mdc#<section>` or `@skills/class-refactoring/SKILL.md`) satisfied by the change.

---

## Refactoring proposals

> Render only when at least one out-of-scope structural improvement is justified by a rule. Omit the entire section when there are no items.

1. **Title:** short, actionable issue title
   **Scope:** affected file(s) or area
   **Reason:** rule violated + why it matters
   **Approach:** brief description

---

## Database Analysis

> Render only when the diff touches database operations (raw SQL, Eloquent / query-builder calls, eager loads, model scopes, ModelManager / Repository methods, migrations, seeders, DynamoDB / NoSQL access) **and** at least one finding is produced by `@skills/mysql-problem-solver/SKILL.md`. Omit the entire section when no DB operations are present in the diff, or when DB ops are present but no findings result — never leave a placeholder or fold it into Coverage.
>
> Report only findings (errors) and their fix recommendations. Never include the trigger decision, an inspected `file:line` list, or an EXPLAIN / static-analysis summary — those belong to the internal investigation, not the published review.

- **Findings:**
  1. **{Critical / Moderate / Minor}** — `file:line` — one-sentence problem
     **Suggested Fix:** {query rewrite to reuse an existing index per `@rules/sql/optimalize.mdc`, batch operation per "Batch over per-row operations", or new-index proposal justified by EXPLAIN when no existing index covers the query}

---

## Architecture

> **Laravel-only, mandatory on every CR run (issue #530).** Render this section on every Laravel project (`laravel/framework` is in `composer.json` `require`) regardless of which files the diff touches — the architecture walk per `@skills/code-review/SKILL.md` Core Analysis "Architecture conformance (Laravel) — mandatory standalone walk-through" never skips on Laravel.
>
> - **Walk produced findings →** render them below under Critical / Moderate / Minor severity sub-headings (same six reproducer fields as `## Findings`), each citing the offending `file:line` and the specific subsection of `@rules/laravel/architecture.mdc` (`Business Logic Layers`, `Actions`, `Action Rules`, `Model Services`, `Repositories and ModelManagers`, `DTOs`, `Data Modification (DRY)`, `Data Builders`, `Validation Rules (Traits)`, `Data Validators`, `Controllers and Other Entry Points`, `Resource Controllers`, `Single-Action Controllers`, `Livewire`, `Custom Helpers`).
> - **Walk produced zero findings →** still render the heading and the literal status line `Status: walked, 0 findings` so the reviewer can see the check ran. Do not collapse the section, do not move the status into the summary line, and do not replace it with `n/a` / `clean` / `not applicable`.
> - **Non-Laravel projects →** omit the entire `## Architecture` section. Do not emit a "skipped" placeholder.

Status: walked, 0 findings  *(use this literal line when the walk is clean; otherwise replace with the severity sub-headings and findings)*

### 🔴 Critical 1. <short title>

(same six fields as `## Findings` — Location / Rule / Impact / Faulty Example / Expected behavior / Test hint / Suggested fix)

### 🟠 Moderate 1. <short title>

(same six fields as Critical)

### 🟡 Minor 1. <short title>

- **Location:** `path/to/file.php:42`
- **Rule:** `@rules/laravel/architecture.mdc#<subsection>`
- **Note:** one sentence. Faulty Example / Expected behavior / Test hint / Suggested fix may be omitted when no behavior change is implied.

---

## Coverage

> Render this section **only** when the coverage gate produced something to report — uncovered changed lines (Critical findings) or unavailable / non-runnable coverage tooling (Critical finding). When every changed line is at 100% coverage and the tool ran successfully, omit the entire `## Coverage` section, the `Coverage:` header line, and the `coverage …` slot in the summary line — the Counts line is the clean signal.

- **Tool:** {project's available coverage tooling used to verify the changed files (Phing coverage target, Composer `test:coverage` / `coverage`, or `vendor/bin/pest --coverage-clover` / PHPUnit `--coverage-clover`) — or "coverage tooling unavailable — <reason>". Assess the changed files only; do not gate on a project-wide coverage percentage.}
- **Command:** `<exact command run — e.g. `vendor/bin/pest --coverage-clover=coverage.xml`>`
- **Result:** {list of uncovered added/changed lines — which must also appear as Critical findings — or "coverage tooling unavailable — <reason>"}

---

**Summary:** {n} Critical · {n} Moderate · {n} Minor · {n} Refactoring{` · coverage {result}` — appended only when the `## Coverage` section is rendered; omitted on a clean 100% pass}
