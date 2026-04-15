---
name: refactor-entry-point-to-action
description: "Use when refactoring any entry point logic from a controller/job/command/listener into an Action class. Enforces Action pattern rules from project.mdc and related architecture rules."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Read all architecture rules that define Action pattern requirements before writing any code.
- Preserve behavior: refactor orchestration location, not business result.
- In this iteration, do not report code review output to any third-party service.
- After generating or updating code, run immediate internal code review focused on architecture and fix findings ASAP.

**Use when:**
- A controller entry point (or job/command/listener/**Livewire component** entry point) contains orchestration logic and must be migrated to Action pattern.
- You want to run this skill manually in Cursor for a specific entry point method.

**Manual invocation in Cursor:**
- Call this skill and always include:
  - File path of the entry point class.
  - Expected target Action class name and domain folder (or ask to propose one).
  - Constraints if API response format/signature must remain unchanged.
- Input template:
  - `Refactor entry point <Class::method> in <path> to Action pattern.`
  - `Keep behavior and response contract unchanged.`
  - `Create/use Action in app/Actions/<Domain>/<ActionName>.php and wire entry point to delegate.`
  - `Respect project.mdc Action architecture rules.`

**Mandatory Action pattern requirements:**
- **All business logic is allowed only in classes that follow the action pattern!**
- Mandatory flow: `Controller/Job/Command/Listener/Livewire Component -> Action -> ModelService -> Repository (read) / ModelManager (write)`.
- New Action must be placed under `app/Actions/**` in a domain-specific subfolder.
- Action class must be `final readonly`.
- Action must expose exactly one public business entry point: `__invoke(...)` with explicit return type.
- Action must stay clean and simple: minimal orchestration surface, no duplicated branches, no dead paths.
- Action should be as optimized as possible for readability and runtime (avoid redundant mapping, calls, or temporary structures).
- No direct Eloquent queries and no `DB::` calls inside the Action.
- Action orchestrates only: data validator invocation, mapping, and delegation; heavy shared logic belongs to Services.
- **Single-use Service/Facade method rule (Action pattern):** If the Action calls a Service or Facade method that is used only once in the entire codebase, move the business logic from that Service/Facade method directly into the Action and remove the original Service/Facade method.
- **BaseModelService pattern:** When delegating to Services, ensure model-oriented services extend `BaseModelService` and implement `getModelManager()`, `getRepository()`, and `getModelClass()` (see `vendor/pekral/arch-app-services/examples/Services/User/UserModelService.php`). Services that do not primarily serve a single model must be refactored into Action pattern classes instead.
- **Actions must not contain inline validation logic**: do not throw `ValidationException` directly or call `Validator::make()` inside Actions. Extract all validation into a dedicated Data Validator class under `app/DataValidators/{Domain}/`.
- Data Validators are `final readonly` classes with constructor DI and a single `validate()` method that throws `ValidationException` on failure.
- Actions call the Data Validator before proceeding with business orchestration.
- Entry point method must become thin and only delegate to Action using direct invocation syntax `$action($params)`.
- **Livewire components** are entry points: component action methods (e.g. `save()`, `submit()`, `delete()`) must delegate to Action classes. The component class lives in `app/Livewire/` with a separate Blade view in `resources/views/livewire/`. Single-file (Volt) components are forbidden.
- **Invokeable call convention:** Always use `$action($params)` to call Actions — never use `$action->__invoke($params)`. PHP natively routes the call to `__invoke()`, making the explicit form redundant.
- Add or update PHPDoc where needed so PHPStan can infer intent/types without ambiguity (especially DTO shapes, iterable generics, and non-obvious contracts).

**Steps:**
1. Analyze current implementation of the target entry point method and identify orchestration steps.
2. Create a dedicated Action (one use case = one Action) in the correct domain folder under `app/Actions/**`.
3. Move orchestration from controller method into Action `__invoke(...)`.
4. Keep reads in Repository and writes in ModelManager; if missing, introduce or reuse proper layer classes.
5. Update controller method to dependency-inject and call the Action using direct invocation syntax `$action($params)` (never `$action->__invoke($params)`).
6. Keep account/multitenancy scope intact in all delegated calls.
7. Ensure method signatures and returned response format stay backward compatible.
8. Add or update PHPDoc to satisfy static analysis quality for touched PHP code.
9. Run an internal architecture-first code review of the generated changes (no third-party reporting in this iteration).
10. If the review finds critical or medium issues, fix them immediately and repeat the review until no such findings remain.
11. If PHP files changed, run required project checks/fixers and resolve all issues.
12. If new database migrations were created during the changes, run them (`php artisan migrate`) before running tests or creating a PR.
13. Add or update tests to fully cover touched logic and preserve behavior.

**Do not:**
- Do not place validation logic (throwing `ValidationException`, calling `Validator::make()`) directly in Action classes — use Data Validators.
- Do not keep business branching/orchestration in the controller method.
- Do not place Action classes outside `app/Actions/**`.
- Do not create multiple public business methods in an Action.
- Do not bypass Repository/ModelManager boundaries.
- Do not change unrelated behavior while refactoring.
- Do not create `final class` wrappers with a single static method for simple utility logic — use a global helper function in `app/helpers.php` instead (see Custom Helpers in `@rules/laravel/architecture.mdc`).

**After completing the tasks**
- Run @skills/code-review/SKILL.md for the current changes.
- Run @skills/process-code-review/SKILL.md to resolve any findings from the code review.

**Definition of done:**
- Target entry point method is thin and delegates to a dedicated Action.
- Action respects all project Action-pattern constraints.
- Internal architecture-focused review was executed and all critical/medium findings were fixed before completion.
- Action implementation is clean, simple, and optimized without changing behavior.
- Required PHPDoc for PHPStan is present on touched PHP code where needed.
- Tests cover the refactored flow (including failure/edge paths where applicable).
- Required project quality checks pass for changed files.
