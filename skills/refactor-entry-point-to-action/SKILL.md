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
- Apply `@rules/refactoring/general.mdc` — incremental migration only, never a big-bang rewrite. The **Test Coverage Contract** in that rule is binding: 100% coverage of the target lines must exist in a dedicated `test(scope): cover <area> before refactor` commit *before* the entry-point change lands, and the assertion logic of those tests must remain unchanged through the refactor commit.
- Apply `@rules/php/core-standards.mdc`.
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Preserve behavior, signatures, response contracts, and tenant/account scope. Rewrite the entry-point code **strictly per the applied rules** — anything that would deviate (parameter count, naming, nesting, layer placement, validation home) is rewritten until it complies.
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
- **Livewire entry points — Blade view co-refactor.** When the entry point being refactored is a Livewire component, also inspect its Blade view (`resources/views/livewire/<...>.blade.php`). Walk it against the triggers in `@rules/laravel/livewire.mdc` *HTML / Blade Layout Splitting* (repeated markup, >150 Blade lines, self-contained `wire:*` cluster, self-contained data shape, cross-page reuse, independent loading / empty / error state, distinct named UI concern). For every match, propose the extraction in the refactor plan: name the extracted concern, pick the correct component type per the rule's **Component-type decision** (Livewire only for stateful / lifecycle / server-interactive blocks; Blade for stateless presentation — never a Livewire wrapper around a pure presentational block), and place the new component under the concern's domain folder (`app/Livewire/<Domain>/` or `resources/views/components/<domain>/`). The extracted children must satisfy the **Reusability contract** in the rule (typed input, one concern, no business logic, events not parent reach-through, independently renderable, concern-based name). **Apply-mode requirement:** the extractions must land in a dedicated `refactor(scope): split <view> into reusable components` commit that follows the Action-extraction commit and respects the **Test Coverage Contract** in `@rules/refactoring/general.mdc` in spirit — every rendered branch of the touched view is covered by a Livewire / Blade feature test committed before the layout refactor, and those feature tests stay green through the refactor commit unchanged. PHP `--coverage-clover` does not measure `.blade.php` line-by-line, so the binding gate is feature-test parity, not a numeric coverage percentage on the view file. **CR-mode requirement:** the proposals must be emitted as markdown findings (`file:line`, concern name, Livewire-vs-Blade choice, target folder, rule reference) and the skill stops without modifying code.

## Read, Map & Verify before refactoring (mandatory pre-flight)

> **`MODE=cr`:** perform Read and Map read-only to ground the extraction proposal in the real code; Verify is the read-only audit. Do not modify code.

Reading, mapping, and verifying come first; refactoring comes last. This pre-flight is **blocking** — do not edit a single line of production code until all three steps pass, and never act on an assumption you have not confirmed by reading the code.

1. **Read** — open and read the actual entry point and the code it orchestrates (called Services / Repositories / ModelManagers, the Blade view for Livewire components, related tests, configuration). Confirm what the orchestration does by reading it, not by guessing from names.
2. **Map** — map the change's blast radius: the entry point's response contract and signature, its callers and routes, the orchestration that must move into the Action, and the existing Actions / Data Validators / `app/Concerns/` traits to reuse instead of reinventing.
3. **Verify** — check your assumptions against the real code and its observed behavior so the extraction preserves behavior, signatures, and tenant/account scope. If reading and mapping contradict the task framing, stop and surface the discrepancy instead of refactoring on a wrong premise.

Only after Read, Map, and Verify are complete may the Test Coverage Gate and the extraction proceed.

## Execution

> **`MODE=cr`:** run steps 1–2 read-only, then emit the Action-extraction proposal described under Modes and stop — do not run steps 3–12 (they author tests, create files, run fixers, and chain reviews).

1. Inspect the target entry point and identify orchestration responsibilities.
2. Scan touched files for obvious pre-existing issues that would block or compromise the refactor. Fix only safe, relevant issues; keep unrelated cleanup out of scope.
3. **Test Coverage Gate (blocking pre-flight).** Verify coverage of the *current* entry-point method that the refactor will touch, using the project's available coverage tooling scoped to that file (per `@rules/php/core-standards.mdc` Testing section). Every line, branch, and condition must already be at 100%. If coverage is below 100% on the target lines, **stop and write the missing tests first** via `@skills/create-test/SKILL.md`, then commit them in a dedicated `test(scope): cover <area> before refactor` commit per `@rules/git/general.mdc` Allowed Types. The pre-refactor coverage commit and the refactor commit are **always two separate commits**. Only after the gate is green may the refactor proceed.
4. Create or reuse a dedicated Action in the correct domain folder.
5. Move orchestration from the entry point into the Action `__invoke(...)`.
6. Extract inline validation into a dedicated Data Validator (using validation traits from `app/Concerns/`) if needed.
7. Preserve repository/service/manager boundaries and multitenancy/account scope.
8. Update the entry point to delegate via `$action(...)` and keep its public contract unchanged.
9. **Do not modify assertion logic of pre-existing tests inside the refactor commit.** The pre-refactor coverage commit fixed the behavior-preservation contract; the refactor commit changes structure only. Mechanical renames forced by the refactor itself (namespace move, constructor / argument shape forced by the extracted DTO) are the only allowed test edits and must be flagged in the commit body. If an assertion would have to change to make the refactor green, you are no longer refactoring — split the behavior change into its own commit. New tests covering newly introduced code paths (e.g. the Data Validator's failure modes that did not exist before) belong in a separate `test(scope): …` commit *after* the refactor. **After the refactor commit, re-run the coverage tooling scoped to the changed files and confirm coverage stayed 100% with the pre-existing assertions passing unchanged** — a refactored line that is no longer covered signals an untested path to fix, never a number to restore by editing the pre-refactor tests (step 4 of the **Test Coverage Contract** in `@rules/refactoring/general.mdc`).
10. Discover available fixers and checkers (prefer Phing targets from `build.xml`/`phing.xml`; fall back to Composer scripts in `composer.json`). Run fixers first, then checkers/analyzers on all changed files. Resolve all reported issues.
11. **Run the review inline.** Invoke `@skills/code-review/SKILL.md` directly in this skill's context, passing the refactor commit range plus the instruction to return Critical / Moderate / Minor findings with their reproducer fields. Do not dispatch the review as a subagent — run it sequentially in the current context.
12. Run `@skills/process-code-review/SKILL.md` inline (per its own contract) and fix critical or medium findings before finishing.

## Do not
- Do not leave business orchestration in the entry point.
- Do not place Actions outside `app/Actions/**`.
- Do not add multiple public business methods to an Action.
- Do not place validation logic directly inside an Action.
- Do not bypass repository/model-manager/service boundaries.
- Do not introduce unrelated behavioral changes.
- Do not add speculative flexibility, configurability, or error handling while extracting the Action. The extraction moves orchestration as-is into the Action and the Data Validator; new parameters, mode switches, strategy hooks, retry knobs, or guards for scenarios the original entry point did not handle belong to a separate `feat(scope): …` commit and a separate issue. Apply the **Simplicity First** rule from `@skills/class-refactoring/SKILL.md` Refactoring Guidelines and the YAGNI rules in `@rules/php/core-standards.mdc` Design Principles.

## Done when

**`MODE=apply`:**
- The target entry point is thin and delegates to a dedicated Action.
- The Action follows project Action-pattern rules.
- Validation is delegated to a dedicated Data Validator (using validation traits from `app/Concerns/`) when applicable.
- Behavior, signatures, and response format remain unchanged.
- The pre-refactor coverage commit reports 100% coverage on the entry-point lines that were refactored, and the assertion logic in those tests is unchanged through the refactor commit (mechanical renames flagged in the commit body excepted).
- After the refactor, coverage scoped to the changed files was re-verified and **stayed** 100% with the pre-existing assertions passing unchanged.
- New tests covering paths introduced by the refactor (e.g. Data Validator failure modes) live in a separate `test(scope): …` commit after the refactor.
- Fixers and checkers ran clean on all changed files.
- Internal architecture-focused review was completed and important findings were fixed.

**`MODE=cr`:** the Action-extraction proposal was emitted as markdown for every qualifying entry point in the diff (entry-point `Class::method`, orchestration to move out, target Action / Data Validator, rule reference) and **no files were created or modified**.
