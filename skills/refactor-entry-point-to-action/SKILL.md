---
name: refactor-entry-point-to-action
description: "Use when refactoring controller, job, command, listener, or Livewire entry-point logic into a dedicated Action class while preserving behavior and response contracts."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`.
- Read and follow `@rules/laravel/architecture.mdc` before making changes.
- Preserve behavior, signatures, response contracts, and tenant/account scope.
- Do not report review output to any third-party service.
- After changes, run an internal architecture-first review and fix important findings immediately.

## Use when
- A controller, job, command, listener, or Livewire component method contains business orchestration that should be moved into an Action.
- You want a thin entry point that delegates one use case to one Action.

## Manual invocation in Cursor
Always include:
- Entry-point file path
- Target method (`Class::method`)
- Expected Action class name and domain folder, or ask to propose one
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
- Do not place inline validation inside the Action. Use a dedicated Data Validator under `app/DataValidators/<Domain>/`.
- Do not use direct Eloquent queries or `DB::` calls inside the Action.
- Keep reads in repositories and writes in model managers/services according to project architecture.
- Add or update PHPDoc where needed for PHPStan clarity.

## Execution
1. Inspect the target entry point and identify orchestration responsibilities.
2. Scan touched files for obvious pre-existing issues that would block or compromise the refactor. Fix only safe, relevant issues; keep unrelated cleanup out of scope.
3. Create or reuse a dedicated Action in the correct domain folder.
4. Move orchestration from the entry point into the Action `__invoke(...)`.
5. Extract inline validation into a dedicated Data Validator if needed.
6. Preserve repository/service/manager boundaries and multitenancy/account scope.
7. Update the entry point to delegate via `$action(...)` and keep its public contract unchanged.
8. Add or update tests for the refactored flow and important failure paths.
9. Run required checks/fixers for changed PHP files and resolve issues.
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
- The target entry point is thin and delegates to a dedicated Action.
- The Action follows project Action-pattern rules.
- Validation is delegated to a dedicated Data Validator when applicable.
- Behavior, signatures, and response format remain unchanged.
- Tests cover the refactored flow and important edge/failure paths.
- Required checks pass for changed files.
- Internal architecture-focused review was completed and important findings were fixed.
