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
- Apply @rules/php/core-standards.mdc
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Apply @rules/code-testing/general.mdc
- Never change behavior
- Keep public API stable unless explicitly required

---

## Execution

- Analyze the class and identify the highest-impact refactoring.
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
- Remove duplication (DRY).
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
