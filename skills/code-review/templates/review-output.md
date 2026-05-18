# Code Review

**Status:** clean / needs-fix
**Counts:** Critical {n} · Moderate {n} · Minor {n} · Refactoring {n}
**Coverage:** {result} (tool: {name or "not available — <reason>"})

---

## Previous CR Status

> Only on follow-up reviews. Omit on first review.

| # | Finding | Status |
|---|---------|--------|
| 1 | <previous finding title> | ✅ Resolved / ⏳ Deferred / ❌ Still open |

---

## Findings

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

> Only items on lines touched by this PR (added or modified). Each item must reduce tech debt — no stylistic preferences.

1. **Location:** `path/to/file.php:42`
   **Problem:** one sentence.
   **Refactor:** concrete consolidation step (Data Builder / DTO / Service / Action / Repository / ModelManager).
   **Why:** rule reference (`@rules/laravel/architecture.mdc#<section>` or `@skills/class-refactoring/SKILL.md`) satisfied by the change.

---

## Refactoring proposals

> Out-of-scope structural improvements justified by rules. Omit when none.

1. **Title:** short, actionable issue title
   **Scope:** affected file(s) or area
   **Reason:** rule violated + why it matters
   **Approach:** brief description

---

## Database Analysis

> Mandatory when the diff touches database operations (raw SQL, Eloquent / query-builder calls, eager loads, model scopes, ModelManager / Repository methods, migrations, seeders, DynamoDB / NoSQL access). Omit the entire section when no DB operations are present in the diff — never leave a placeholder or fold it into Coverage.

- **Trigger:** {DB operations detected vs. trigger skipped — never empty when the section is present}
- **Inspected:** {bullet list of `file:line` for each query / migration / Eloquent statement analysed}
- **EXPLAIN / static analysis:** {summary of `@skills/mysql-problem-solver/SKILL.md` output — note "no DB access — static analysis only" when EXPLAIN could not be run}
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
