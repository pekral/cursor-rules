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

## Coverage

- **Tool:** {discovered coverage command name, or "not available — <reason>"}
- **Command:** `<exact command run>`
- **Result:** {% covered for changed lines, or list uncovered added/changed lines — which must also appear as Critical findings}

---

**Summary:** {n} Critical · {n} Moderate · {n} Minor · {n} Refactoring · coverage {result}
