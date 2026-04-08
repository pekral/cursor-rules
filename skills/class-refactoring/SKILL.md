---
name: class-refactoring
description: "Refactors PHP classes following Laravel best practices and SOLID principles. Performs safe, incremental changes that preserve behavior, improve structure, readability, and maintainability. Use when refactoring a class, improving class structure, cleaning up a class, applying refactoring, or reducing complexity in a class."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Class Refactoring

## Purpose

Perform safe, structured, incremental class refactoring that improves code quality while preserving existing behavior. Each refactoring must follow a clear analysis-plan-execute-validate cycle and produce minimal, reviewable changes.

**Constraint:**
- Apply @rules/base-constraints.mdc
- Always apply @skills/smartest-project-addition/SKILL.md to select the single highest-impact refactoring direction before implementing changes.
- Apply @rules/architecture-patterns.mdc
- Apply @rules/testing-conventions.mdc

## Preconditions

Before making any changes, the agent MUST:

- Read and understand the entire class.
- Identify all responsibilities the class holds.
- Identify dependencies (injected services, traits, base classes, interfaces).
- Confirm framework and architecture constraints from `rules/**/*.mdc`.
- If the class context is unclear, run @skills/analyze-problem/SKILL.md first.

Do not refactor blindly — every change must have a clear reason.

## Current State Analysis

Analyze the class for:

1. **Responsibilities** — does the class have a single responsibility or multiple?
2. **Method complexity** — are there methods exceeding ~30 lines or deep nesting?
3. **Duplication** — is there repeated logic within or across methods?
4. **Naming clarity** — do names reveal intent?
5. **Coupling** — is the class tightly coupled to concrete implementations?
6. **Design issues** — magic numbers, stateful behavior, mixed abstraction levels?

See @references/class-design.md for cohesion, coupling, and naming guidelines.

## Refactoring Plan

Before changing code, define a plan:

1. **What** will be changed (specific methods, properties, or structure).
2. **Why** it improves the code (which principle or pattern applies).
3. **Risk** — what could break (callers, tests, public API).
4. **Order** — sequence of changes from safest to riskiest.

See @references/refactoring-principles.md for SRP, DRY, KISS, YAGNI guidelines.
See @references/risk-management.md for regression avoidance and validation strategy.

## Incremental Changes

Refactoring MUST be done in small, logical steps:

- One concern per change.
- Each step must leave the code in a working state.
- Avoid large rewrites — prefer step-by-step transformation.
- Keep commits reviewable (small diff, clear purpose).

See @examples/refactor-small.md, @examples/refactor-srp.md, @examples/refactor-extract-method.md for patterns.

## Code Quality Rules

- Analyze the class and complete the TODO list tasks.
- Preserve functionality — change how, not what.
- Focus on recently modified code unless instructed otherwise.
- No increase in public API surface without strong justification.
- Clean, modern, optimized code.
- Stateless PHP classes.
- Collections over `foreach` where appropriate.
- PHPDoc for PHPStan analysis. PHPDoc content: describe business logic and general purpose; avoid listing method calls or implementation steps.
- Complex logic commented.
- No magic numbers.
- No deep nesting.
- Prefer small, focused functions.
- English comments only.
- Spatie DTOs (Spatie Laravel Data) instead of arrays (except Job constructors). Use PHP attributes for property mapping — never override `from()` solely to rename keys. Apply `#[MapInputName(SnakeCaseMapper::class)]` at class level for snake_case-to-camelCase input mapping, or `#[MapName(SnakeCaseMapper::class)]` when the DTO is also serialized to output. Custom named static constructors (e.g. `fromModel()`, `fromRequest()`) are allowed for domain-specific data transformation.
- **`?array` is forbidden:** Any use of `?array` as a type hint must be replaced with a typed collection, DTO, or explicit `array<Type>|null`. Vague nullable arrays hide structure and break static analysis.
- **PHP array key type safety:** When refactoring associative arrays with dynamic keys, apply safe key strategies: use stable prefixed keys (`'user:' . $id`, `'postal:' . $postalCode`, `'ext:' . $externalReference`); prefer a dedicated collection or value object when the key is domain-significant; prefer `list<T>` when the structure is a list, not a map; prefer explicit validation or normalization before using external values as array keys; where relevant, prefer `array<non-decimal-int-string, T>` over misleading `array<string, T>`.
- Laravel helpers over native PHP when appropriate.
- When changing Eloquent models, migrations, or factories, do not duplicate column defaults that already exist in the database schema; see `@rules/laravel/architecture.mdc` (Schema defaults, Migrations).
- When changing Laravel tests that queue jobs, dispatch only via `JobClass::dispatch(...)` per `@rules/laravel/architecture.mdc` Testing.
- DRY principle — eliminate duplicates.
- Remove obvious comments; keep PHPStan-relevant docs.
- Single Responsibility Principle.
- Extract private methods if body exceeds ~30 lines.
- No single-use variables.
- Extract intention-revealing private methods.
- **Livewire components (only in Livewire projects):** Delegate all business logic to Action classes following the mandatory flow: `Livewire Component -> Action -> ModelService -> Repository/ModelManager`.
- Separate orchestration layer from business logic.
- Split by responsibility.
- Centralize business rules.
- Business logic duplication is not allowed.
- Method signatures must remain expressive and minimal.
- Match test variable names to actual use cases.
- New tests must cover relevant code.
- Remove coverage files after verification.

## Forbidden Patterns

- Rewriting entire class without clear justification.
- Mixing refactoring with feature changes.
- Introducing new abstractions without justification.
- Renaming without purpose.
- Increasing complexity during refactoring.
- Modifying existing tests (unless refactoring requires it for consistency).

## Validation

After refactoring:

1. Verify code coverage — all changes must be covered by tests.
2. For all changes in the current branch, analyze code coverage and ensure 100% coverage. Add missing tests.
3. Confirm no behavioral changes — method outputs and side effects remain identical.
4. Check for regressions — run tests for affected files.
5. Verify public API stability — no signature changes without justification.

## Output Contract

```
## Current Analysis
<Class responsibilities, complexity issues, duplication, design problems>

## Refactoring Plan
<What will change, why, which principles apply, risks>

## Changes
<Description of each incremental change performed>

## Validation
<Test results, coverage status, behavior consistency confirmation>

## Risks
<Potential regressions, edge cases, areas to monitor>
```

**Confidence notes:** If the refactoring relies on assumptions about usage patterns or caller behavior, append a brief note explaining what is uncertain.

**After completing the tasks**
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
