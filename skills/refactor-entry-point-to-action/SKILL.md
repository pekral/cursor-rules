---
name: refactor-entry-point-to-action
description: "Use when refactoring controller, job, command, listener, or Livewire entry-point logic into a dedicated Action class while preserving behavior and response contracts."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Modes

This skill runs in one of two modes, selected by the caller via `MODE` (default `apply`):

- **`apply` (default)** — perform the entry-point → Action refactoring: create / update the Action, move orchestration, run fixers / checkers, and chain the After Completion review. The Execution and Done-when steps below behave as written.
- **`cr` (read-only lens — invoked by `@skills/code-review/SKILL.md`, `code-review-github`, `code-review-jira`)** — **never modify code, never create files, never stage / commit / push, never run fixers or checkers, and never chain `code-review` / `process-code-review`.** Scope the analysis to entry points (controller / job / command / listener / Livewire) touched by the PR diff that still hold business orchestration, and return — as markdown only — the proposed Action extraction for each: the entry-point `Class::method`, the orchestration that should move out, the target `app/Actions/<Domain>/<ActionName>` and Data Validator, and the rule reference. The CR folds these into its **Refactoring (DRY / tech debt)** section (in-scope) or **Refactoring proposals** section (out-of-scope). Execution steps 3–11 below apply to `MODE=apply` only.

## Constraints
- Apply `@rules/refactoring/general.mdc` — incremental migration only, never a big-bang rewrite.
- Apply `@rules/php/core-standards.mdc`.
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Preserve behavior, signatures, response contracts, and tenant/account scope.
- Do not report review output to any third-party service.
- After changes (`MODE=apply` only), run an internal architecture-first review and fix important findings immediately. In `MODE=cr` there are no changes — emit the Action-extraction proposal and stop.

## Use when
- A controller, job, command, listener, or Livewire component method contains business orchestration that should be moved into an Action.
- You want a thin entry point that delegates one use case to one Action.

## Manual invocation in Cursor
Always include:
- Entry-point file path
- Target method (`Class::method`)
- Expected Action class name and domain folder (optional — the skill proposes a default name and domain when not provided, instead of asking the user)
- Any response/signature compatibility constraints

Example input:
- `Refactor entry point <Class::method> in <path> to Action pattern.`
- `Keep behavior and response contract unchanged.`
- `Create or reuse Action in app/Actions/<Domain>/<ActionName>.php and delegate from the entry point.`
- `Respect @rules/laravel/architecture.mdc.`

## Required architecture
- Entry point must become thin and delegate directly to an Action via `$action(...)`.
- Create one dedicated Action per use case under `app/Actions/<Domain>/`.
- Action class must be `final readonly`.
- Action must expose exactly one public business method: `__invoke(...)` with an explicit return type.
- Action must orchestrate only: validation, mapping, and delegation.
- Do not place inline validation inside the Action. Use a dedicated Data Validator (default location `app/DataValidators/<Domain>/`, but follow the project's existing convention). Data Validators must use validation rules from reusable traits in `app/Concerns/`.
- Do not use direct Eloquent queries or `DB::` calls inside the Action.
- Keep reads in repositories and writes in model managers/services according to project architecture.
- When the orchestration touches the database in a loop, prefer ModelManager batch methods (`batchUpdate`, `batchInsert`) and bulk delete/read patterns (`whereIn(...)->delete()`, `findBy{Attribute}In(...)` keyed in memory) over per-row queries (see `@rules/sql/optimalize.mdc` "Batch over per-row operations"). Per-row queries inside the Action are allowed only when iterations have an unavoidable side-effect dependency that must be justified in a code comment.
- Add or update PHPDoc where needed for PHPStan clarity.

## Execution

> **`MODE=cr`:** run steps 1–2 read-only, then emit the Action-extraction proposal described under Modes and stop — do not run steps 3–11 (they create files, run fixers, and chain reviews).

1. Inspect the target entry point and identify orchestration responsibilities.
2. Scan touched files for obvious pre-existing issues that would block or compromise the refactor. Fix only safe, relevant issues; keep unrelated cleanup out of scope.
3. Create or reuse a dedicated Action in the correct domain folder.
4. Move orchestration from the entry point into the Action `__invoke(...)`.
5. Extract inline validation into a dedicated Data Validator (using validation traits from `app/Concerns/`) if needed.
6. Preserve repository/service/manager boundaries and multitenancy/account scope.
7. Update the entry point to delegate via `$action(...)` and keep its public contract unchanged.
8. Add or update tests for the refactored flow and important failure paths.
9. Discover available fixers and checkers (prefer Phing targets from `build.xml`/`phing.xml`; fall back to Composer scripts in `composer.json`). Run fixers first, then checkers/analyzers on all changed files. Resolve all reported issues.
10. Run `@skills/code-review/SKILL.md` for the current changes.
11. Run `@skills/process-code-review/SKILL.md` and fix critical or medium findings before finishing.

## Do not
- Do not leave business orchestration in the entry point.
- Do not place Actions outside `app/Actions/**`.
- Do not add multiple public business methods to an Action.
- Do not place validation logic directly inside an Action.
- Do not bypass repository/model-manager/service boundaries.
- Do not introduce unrelated behavioral changes.

## Done when

**`MODE=apply`:**
- The target entry point is thin and delegates to a dedicated Action.
- The Action follows project Action-pattern rules.
- Validation is delegated to a dedicated Data Validator (using validation traits from `app/Concerns/`) when applicable.
- Behavior, signatures, and response format remain unchanged.
- Tests cover the refactored flow and important edge/failure paths.
- Fixers and checkers ran clean on all changed files.
- Internal architecture-focused review was completed and important findings were fixed.

**`MODE=cr`:** the Action-extraction proposal was emitted as markdown for every qualifying entry point in the diff (entry-point `Class::method`, orchestration to move out, target Action / Data Validator, rule reference) and **no files were created or modified**.
