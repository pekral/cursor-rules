---
name: class-refactoring
description: Use when refactor PHP classes to improve structure, readability,
  and maintainability while preserving behavior
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Class Refactoring

## Purpose
Improve code structure and quality without changing behavior.

Focus on:
- clarity
- separation of concerns
- testability
- maintainability

---

## Constraints
- Apply @rules/refactoring/general.mdc — shared definition of refactoring, recommended incremental process, and "no big-bang rewrite" rule.
- Apply @rules/php/core-standards.mdc
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Apply @rules/code-testing/general.mdc
- Never change behavior
- Keep public API stable unless explicitly required

---

## Execution

- Analyze the class and identify the highest-impact refactoring.
- Follow the incremental process from `@rules/refactoring/general.mdc` (stabilize → identify entry points → introduce Action pattern → split responsibilities → modernize → DRY → concurrency). Never propose a big-bang rewrite.
- Fix any obvious pre-existing bugs before refactoring (separate commit).
- Apply focused refactoring:
  - simplify structure
  - reduce complexity
  - improve naming
  - extract responsibilities where needed
- Avoid unnecessary changes outside the scope.
- Prefer small, safe transformations over large rewrites.

---

## Refactoring Guidelines

- Ensure single responsibility per class.
- Separate orchestration from business logic.
- **Speculative interfaces:** Collapse project-owned `interface` types that have neither at least two non-test consumers nor at least two non-test implementations back into their concrete class. Test doubles, mocks, and fakes do not count toward either threshold. Implementing a framework or vendor interface (e.g. `ShouldQueue`, `HasLabel`, `Arrayable`) is always allowed. Keep a single-implementation, single-consumer project interface only when there is a documented architectural reason — a published package API surface or a plugin extension point with a written contract. See `@rules/php/core-standards.mdc` Design Principles.
- **Business Logic Layers (Laravel projects only):** Business logic must live in exactly one of the six allowed class types — **Actions**, **Model Services**, **Repositories**, **ModelManagers**, **Data Validators**, **Data Builders** — as defined in `@rules/laravel/architecture.mdc` "Business Logic Layers". When a class file contains business logic that spans more than one of these layers, or contains business logic that does not fit any of them, propose a refactoring that splits the responsibilities into dedicated classes from the six-layer list. Surface every detected violation in the refactoring plan with the target layer for each extracted responsibility.
- Replace per-row DB queries inside loops with batch operations per `@rules/sql/optimalize.mdc` "Batch over per-row operations" — ModelManager `batchUpdate` / `batchInsert`, `whereIn(...)->delete()`, or a single bulk read keyed in memory. Keep per-row work only when an explicit side-effect dependency between iterations cannot be batched.
- Remove duplication (DRY).
- Before modifying code, enumerate every place that modifies data before it is saved or passed downstream (DTO mapping, payload shaping, key renaming, default fallbacks, format normalization, business-driven derivation). Surface the list in the refactoring plan and consolidate duplicates into the canonical layer per `@rules/laravel/architecture.mdc` Data Modification (DRY) section (Data Builder, DTO named constructor, Data Validator, ModelManager, Repository).
- Prefer small, focused methods.
- Extract intention-revealing private methods when it improves clarity.
- Avoid deep nesting and complex conditionals.
- Keep method signatures clear and minimal.

---

## Laravel Context (if applicable)

- Delegate business logic to Actions and Services.
- Do not place business logic in controllers or Livewire components.
- Use existing query scopes instead of duplicating conditions.
- Prefer DTOs over raw arrays when the project uses them.
- Keep Repositories limited to basic, reusable queries. When refactoring uncovers a feature-specific query method on a Repository, move it to a Service (single-model) or an Action (cross-model / cross-feature) that composes basic Repository methods (see `@rules/laravel/architecture.mdc` Repositories and ModelManagers section).

---

## Testing

- Ensure all changes are covered by tests.
- Add missing tests for modified behavior.
- Do not modify existing tests unless necessary for consistency.
- Prefer realistic tests over heavy mocking.

---

## Output

- Refactored code
- Short explanation of changes:
  - what was improved
  - why it matters
- Summary of test coverage impact

---

## Principles

- Preserve behavior — change how, not what
- Prefer clarity over cleverness
- Prefer simple solutions over complex abstractions
- Avoid over-engineering
- Improve only what is necessary

---

## Pre-push quality gates

- Discover available fixers and checkers (prefer Phing targets from `build.xml`/`phing.xml`; fall back to Composer scripts in `composer.json`)
- Run available fixers on all changed files and fix any violations
- Run available checkers/analyzers on all changed files and resolve all reported errors

## After Completion

- Run @skills/code-review/SKILL.md
- Resolve findings via @skills/process-code-review/SKILL.md
