# Code Review

> **Section visibility — render only sections that have content.** Always render the header block (Status / Counts / Coverage), the `## Coverage` section, and the final `Summary` line. Every other section is conditional: omit its heading and body entirely when it has no items. Never emit `None.` / `Not applicable.` / `n/a` placeholders for empty sections — drop the whole section instead. The Counts line in the header is the single source of "zero" signal; the goal is a clean, scannable PR comment a human can read at a glance.

**Status:** clean / needs-fix
**Counts:** Critical {n} · Moderate {n} · Minor {n} · Refactoring {n}
**Coverage:** {result} (tool: {name or "not available — <reason>"})
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

## Coverage

- **Tool:** {discovered **diff-scoped** coverage script (Phing `test:coverage:diff` / `coverage:diff`, Composer `test:coverage:diff`, or project-specific `*coverage*diff*`) — or "diff-scoped tooling unavailable — <reason>". Never the full-suite `test:coverage` / `coverage` / Phing `coverage` — full-suite belongs to release gates, not CR.}
- **Command:** `<exact command run — e.g. `composer test:coverage:diff`>`
- **Result:** {% covered for changed lines, or list uncovered added/changed lines — which must also appear as Critical findings}

---

**Summary:** {n} Critical · {n} Moderate · {n} Minor · {n} Refactoring · coverage {result}
