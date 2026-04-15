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

**Steps:**
- Before implementing the main change, scan the affected files for pre-existing bugs (broken logic, incorrect behavior, type errors, deprecated patterns). Fix all identified pre-existing bugs in a separate commit before the main implementation commit.
- Analyze the class and complete the TODO list tasks.
- Verify code coverage after refactoring.
- For all changes in the current branch, analyze code coverage and ensure that all changes are covered by tests. Add any missing tests to ensure 100% coverage.
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
- **Endpoint I/O typing:** Controller actions must accept a FormRequest (or typed DTO) and return a typed response (Resource, DTO, or response class). Never pass raw `$request->all()` arrays or return ad-hoc associative arrays — the contract must be explicit.
- Spatie DTOs (Spatie Laravel Data) instead of arrays (except Job constructors). Use PHP attributes for property mapping — never override `from()` solely to rename keys. Apply `#[MapInputName(SnakeCaseMapper::class)]` at class level for snake_case-to-camelCase input mapping, or `#[MapName(SnakeCaseMapper::class)]` when the DTO is also serialized to output. Custom named static constructors (e.g. `fromModel()`, `fromRequest()`) are allowed for domain-specific data transformation.
- **`?array` is forbidden:** Any use of `?array` as a type hint must be replaced with a typed collection, DTO, or explicit `array<Type>|null`. Vague nullable arrays hide structure and break static analysis.
- **PHP array key type safety:** When refactoring associative arrays with dynamic keys, apply safe key strategies: use stable prefixed keys (`'user:' . $id`, `'postal:' . $postalCode`, `'ext:' . $externalReference`); prefer a dedicated collection or value object when the key is domain-significant; prefer `list<T>` when the structure is a list, not a map; prefer explicit validation or normalization before using external values as array keys; where relevant, prefer `array<non-decimal-int-string, T>` over misleading `array<string, T>`.
- Laravel helpers over native PHP when appropriate.
- **Custom Helpers:** Small utility functions that don't belong to any specific class (cache key generators, input validators, formatters) must be implemented as global helper functions in `app/helpers.php` (autoloaded via `composer.json` `files` key). Follow the [Laravel News helpers pattern](https://laravel-news.com/creating-helpers): plain functions, no wrapper classes, snake_case names. Do not create `final class` wrappers with a single static method for simple utility logic — use a helper function instead.
- When changing Eloquent models, migrations, or factories, do not duplicate column defaults that already exist in the database schema; see `@rules/laravel/architecture.mdc` (Schema defaults, Migrations).
- **Eloquent query scopes (only in Laravel projects):** Always use defined model scopes instead of inline `->where()` calls when a matching scope exists on the model. If a reusable query condition appears in the changed code and no scope exists yet, extract it into a query scope on the model. See `@rules/laravel/architecture.mdc`.
- When changing Laravel tests that queue jobs, dispatch only via `JobClass::dispatch(...)` per `@rules/laravel/architecture.mdc` Testing.
- DRY principle — eliminate duplicates.
- Remove obvious comments; keep PHPStan-relevant docs.
- Single Responsibility Principle.
- Extract private methods if body exceeds ~30 lines.
- No single-use variables.
- Extract intention-revealing private methods
- **Livewire components (only in Livewire projects):** Delegate all business logic to Action classes following the mandatory flow: `Livewire Component -> Action -> ModelService -> Repository/ModelManager`.
- Separate orchestration layer from business logic
- Split by responsibility
- Centralize business rules
- Business logic duplication is not allowed.
- **Service single-responsibility:** A service must not mix business logic with cross-cutting concerns (validation, email/notification dispatch, logging, metrics). Extract cross-cutting work into middleware, events/listeners, or dedicated classes.
- **Middleware for cross-cutting concerns:** Logging, authentication, metrics, retry logic, and rate limiting belong in middleware (HTTP middleware, job middleware, or pipeline stages) — never copy-pasted into individual handlers or services.
- **Façade simplicity:** Expose a simple, intention-revealing API to callers. Hide implementation complexity (retry strategies, idempotence guards, provider selection, circuit breakers) inside the service.
- **Request traceability:** Keep the request path through the system linear and easy to follow. Avoid implicit side effects (hidden event listeners that silently mutate state, magic method calls, model boot events with non-obvious service calls). Each side effect should be explicitly dispatched or documented at the call site.
- **No unnecessary interfaces:** Do not create an interface for a repository or service that has only one implementation and no realistic second implementation. Single-implementation interfaces add indirection without value.
- Method signatures must remain expressive and minimal.
- Match test variable names to actual use cases.
- New tests must cover relevant code.
- Remove coverage files after verification.

  **Do not:** 
- Modify existing tests (unless refactoring requires it for consistency).

**After completing the tasks**
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
