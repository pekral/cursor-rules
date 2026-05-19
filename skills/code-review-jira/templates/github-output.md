# Code Review

> **Section visibility — render only sections that have content.** Always render the header block (Status / Counts / Coverage / Linked-tracker mirror), the `## Coverage` section, and the final `Summary` line. Every other section is conditional: omit its heading and body entirely when it has no items. Never emit `None.` / `Not applicable.` / `n/a` placeholders for empty sections — drop the whole section instead. The Counts line in the header is the single source of "zero" signal; the goal is a clean, scannable PR comment a human can read at a glance.

**Status:** clean / needs-fix
**Counts:** Critical {n} · Moderate {n} · Minor {n} · Refactoring {n}
**Coverage:** {result} (tool: {name or "not available — <reason>"})
**Linked-tracker mirror:** {posted JIRA summary on <KEY> (+ mirrored to GitHub issue #N) | JIRA only — no linked GitHub issue | failed: <reason>}

---

## Previous CR Status

> Render only on follow-up reviews that have at least one previous finding to classify. Omit on first reviews and on follow-up reviews whose previous-finding list is empty.

| # | Finding | Status |
|---|---------|--------|
| 1 | <previous finding title> | ✅ Resolved / ⏳ Deferred / ❌ Still open |

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

> Render only when at least one in-scope refactoring item exists. Only items on lines touched by this PR. Each item must reduce tech debt — no stylistic preferences. Omit the entire section when there are no items.

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

## Coverage

- **Tool:** {discovered coverage command name, or "not available — <reason>"}
- **Command:** `<exact command run>`
- **Result:** {% covered for changed lines, or list uncovered added/changed lines — which must also appear as Critical findings}

---

**Summary:** {n} Critical · {n} Moderate · {n} Minor · {n} Refactoring · coverage {result} · {linked-tracker mirror status}
