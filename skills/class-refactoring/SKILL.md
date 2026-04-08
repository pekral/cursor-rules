---
name: class-refactoring
description: "Use when refactoring PHP classes following Laravel best practices and SOLID principles. Ensures code quality, maintains functionality, improves testability, and achieves 100% code coverage. Focuses on single responsibility, DRY principle, and clean code structure."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Always apply @skills/smartest-project-addition/SKILL.md to select the single highest-impact refactoring direction before implementing changes.
- Apply @rules/architecture-patterns.mdc
- Apply @rules/testing-conventions.mdc

**Scripts:** Use the pre-built scripts in `@skills/class-refactoring/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/changed-classes.sh [base]` | List PHP classes modified in current branch vs base branch |
| `scripts/check-coverage.sh [filter]` | Run PHPUnit with coverage report, optional test filter |

**References:**
- `references/refactoring-checklist.md` — pre-refactoring steps, core principles (SRP, DRY), and prohibitions
- `references/coding-standards.md` — PHP clean code rules, PHPDoc, method design
- `references/type-safety-rules.md` — Spatie DTOs, `?array` prohibition, array key safety
- `references/laravel-conventions.md` — Laravel helpers, Eloquent, Livewire delegation, job dispatch
- `references/testing-requirements.md` — coverage verification, test quality, post-refactoring testing

**Examples:** See `examples/` for expected output format:
- `examples/report-refactoring-complete.md` — clean refactoring with full coverage
- `examples/report-coverage-gap.md` — refactoring where coverage gaps were found and resolved

**Steps:**
1. Run `scripts/changed-classes.sh` to identify modified PHP classes in the current branch.
2. Analyze the class and complete the TODO list tasks per `references/refactoring-checklist.md`.
3. Apply coding standards per `references/coding-standards.md`:
   - Clean, modern, optimized code. Stateless PHP classes. Collections over `foreach` where appropriate.
   - PHPDoc for PHPStan analysis. No magic numbers. No deep nesting. Prefer small, focused functions.
   - Extract private methods if body exceeds ~30 lines. No single-use variables.
4. Enforce type safety per `references/type-safety-rules.md`:
   - Spatie DTOs instead of arrays (except Job constructors). `?array` is forbidden.
   - Apply safe key strategies for associative arrays with dynamic keys.
5. Follow Laravel conventions per `references/laravel-conventions.md`:
   - Laravel helpers over native PHP. Livewire components delegate to Action classes.
   - Do not duplicate column defaults from database schema. Dispatch jobs via `JobClass::dispatch(...)`.
6. Verify and enforce test coverage per `references/testing-requirements.md`:
   - Run `scripts/check-coverage.sh` to verify coverage after refactoring.
   - Add any missing tests to ensure 100% coverage. Remove coverage files after verification.
7. If according to @skills/test-like-human/SKILL.md the changes can be tested, do it.

**Output contract:** For each refactored class, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Class name | Yes | Fully qualified class name |
| Decision | Yes | Refactoring complete / blocked |
| Refactoring direction | Yes | The single highest-impact direction selected |
| Coverage before | Yes | Coverage percentage before changes |
| Coverage after | Yes | Coverage percentage after changes (must be 100%) |
| Public API changed | Yes | Yes / No |
| Tests added | Yes | Count of new tests |
| Tests modified | Yes | Count of modified tests |
| Changes applied | Yes | Bullet list of refactoring actions taken |
| Coverage gaps found | If applicable | Table of gaps and how they were resolved |
| Confidence notes | If applicable | Caveats or assumptions (e.g., existing test modified, edge case) |
