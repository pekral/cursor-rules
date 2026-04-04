---
name: class-refactoring
description: "Use when refactoring PHP classes following Laravel best practices and SOLID principles. Ensures code quality, maintains functionality, improves testability, and achieves 100% code coverage. Focuses on single responsibility, DRY principle, and clean code structure."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Always apply @.cursor/skills/smartest-project-addition/SKILL.md to select the single highest-impact refactoring direction before implementing changes.

**Steps:**
- Analyze the class and complete the TODO list tasks.
- Verify code coverage after refactoring.
- For all changes in the current branch, analyze code coverage and ensure that all changes are covered by tests. Add any missing tests to ensure 100% coverage.
- If new database migrations were created during the changes, run them (`php artisan migrate`) before running tests or creating a PR.
- Preserve functionality — change how, not what.
- Focus on recently modified code unless instructed otherwise.
- No increase in public API surface without strong justification
- Clean, modern, optimized code.
- Stateless PHP classes.
- Collections over `foreach` where appropriate.
- PHPDoc for PHPStan analysis. PHPDoc content: describe business logic and general purpose; avoid listing method calls or implementation steps.
- Complex logic commented
- No magic numbers
- No deep nesting
- Prefer small, focused functions.
- English comments only.
- Spatie DTOs (Spatie Laravel Data) instead of arrays (except Job constructors). Use PHP attributes for property mapping — never override `from()` manually. Apply `#[MapInputName(SnakeCaseMapper::class)]` at class level for snake_case-to-camelCase input mapping, or `#[MapName(SnakeCaseMapper::class)]` when the DTO is also serialized to output.
- **`?array` is forbidden:** Any use of `?array` as a type hint must be replaced with a typed collection, DTO, or explicit `array<Type>|null`. Vague nullable arrays hide structure and break static analysis.
- **PHP array key type safety:** When refactoring associative arrays with dynamic keys, apply safe key strategies: use stable prefixed keys (`'user:' . $id`, `'postal:' . $postalCode`, `'ext:' . $externalReference`); prefer a dedicated collection or value object when the key is domain-significant; prefer `list<T>` when the structure is a list, not a map; prefer explicit validation or normalization before using external values as array keys; where relevant, prefer `array<non-decimal-int-string, T>` over misleading `array<string, T>`.
- Laravel helpers over native PHP when appropriate.
- **Laravel AI SDK:** When implementing AI features in a Laravel project, always use the [Laravel AI SDK](https://laravel.com/docs/13.x/ai-sdk). Never call AI provider APIs directly (e.g., raw OpenAI PHP client) when the Laravel AI SDK covers the use case.
- When changing Eloquent models, migrations, or factories, do not duplicate column defaults that already exist in the database schema; see `@.cursor/rules/laravel/architecture.mdc` (Schema defaults, Migrations).
- When changing Laravel tests that queue jobs, dispatch only via `JobClass::dispatch(...)` per `@.cursor/rules/laravel/architecture.mdc` Testing.
- DRY principle — eliminate duplicates.
- **Validation rules as traits:** Extract reusable validation rules into traits in `App\Concerns` (e.g. `PasswordValidationRules`). Use these traits in FormRequest classes instead of duplicating rule arrays.
- Remove obvious comments; keep PHPStan-relevant docs.
- Single Responsibility Principle.
- Extract private methods if body exceeds ~30 lines.
- No single-use variables.
- Extract intention-revealing private methods
- **All business logic is allowed only in classes that follow the action pattern!**
- **Invokeable call convention:** When calling Action classes, always use direct invocation `$action($params)` — never `$action->__invoke($params)`.
- **Action pattern (only when `vendor/pekral/arch-app-services` exists):** Apply @.cursor/skills/refactor-entry-point-to-action/SKILL.md when the refactored class is a controller, job, command, listener, or **Livewire component** that contains orchestration logic.
- **Single-use Service/Facade method rule (Action pattern):** If an Action calls a Service or Facade method that is used only once in the entire codebase, move the business logic from that Service/Facade method directly into the Action and remove the original Service/Facade method.
- **Invokeable controller rule:** Any controller method that is not a standard CRUD method (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) must be extracted into a dedicated single-action invokeable controller with only `__invoke()`. Resource controllers must only contain CRUD methods.
- **BaseModelService pattern (only when `vendor/pekral/arch-app-services` exists):** All services that primarily work with a specific Eloquent Model must extend `BaseModelService` and implement `getModelManager()`, `getRepository()`, and `getModelClass()` (see `vendor/pekral/arch-app-services/examples/Services/User/UserModelService.php`). Services that do not primarily serve a single model must be refactored into Action pattern classes.
- **Data Validator extraction (only when `vendor/pekral/arch-app-services` exists):** If an Action class contains inline validation logic (throwing `ValidationException` directly or calling `Validator::make()`), extract it into a dedicated Data Validator class under `app/DataValidators/{Domain}/`.
- **Livewire components (only in Livewire projects):** Livewire components are entry points — they must not contain business logic. Split every component into a PHP class (`app/Livewire/`) and a Blade view (`resources/views/livewire/`). Never use single-file (Volt) components. Delegate all business logic to Action classes following the mandatory flow: `Livewire Component -> Action -> ModelService -> Repository/ModelManager`.
- Separate orchestration layer from business logic
- Split by responsibility
- Centralize business rules
- Business logic duplication is not allowed.
- Method signatures must remain expressive and minimal.
- Match test variable names to actual use cases.
- New tests must cover relevant code.
- After generating or modifying tests, verify that all new tests comply with the testing rules in `@.cursor/rules/php/standards.mdc`. Check mock usage specifically: mock only external services (HTTP clients) or to simulate exceptions; remove any constructor mocks, unnecessary mocks, or mocks that can be replaced with real service logic.
- Remove coverage files after verification.

  **Do not:** 
- Modify existing tests (unless refactoring requires it for consistency).

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
