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

### Test Coverage Gate (mandatory pre-flight — issue #493)

Before touching any line of structure, satisfy the **Test Coverage Contract** defined in `@rules/refactoring/general.mdc`:

1. Run the diff-scoped coverage command (`vendor/bin/test-coverage-diff` first, then the fallbacks listed in `@rules/php/core-standards.mdc` Testing section) against the *current* code that the refactor will touch. Every line, branch, and condition must already be at 100%.
2. **If coverage is below 100% on the target lines, stop and write the missing tests first.** Use `@skills/create-test/SKILL.md` to author them; commit them in a dedicated `test(scope): cover <area> before refactor` commit per `@rules/git/general.mdc` Allowed Types. The pre-refactor coverage commit and the refactor commit are **always two separate commits** — never squash them and never mix new tests into the refactor commit.
3. Only after the coverage gate is green may the refactor proceed.

### Refactoring steps

- Analyze the class and identify the highest-impact refactoring.
- Follow the incremental process from `@rules/refactoring/general.mdc` (stabilize → identify entry points → introduce Action pattern → split responsibilities → modernize → DRY → concurrency). Never propose a big-bang rewrite.
- Fix any obvious pre-existing bugs before refactoring (separate commit).
- Apply focused refactoring:
  - simplify structure
  - reduce complexity
  - improve naming
  - extract responsibilities where needed
- **Do not modify pre-existing tests inside the refactor commit.** The safety net authored under the Test Coverage Gate above is what proves behavior is preserved — rewriting or restructuring those tests in the same commit invalidates the proof. The only allowed test edits in the refactor commit are mechanical renames forced by the refactor itself (e.g. namespace move), and they must be flagged in the commit body. New tests that cover newly introduced code paths belong in a separate `test(scope): …` commit *after* the refactor.
- Avoid unnecessary changes outside the scope.
- Prefer small, safe transformations over large rewrites.

---

## Refactoring Guidelines

- Ensure single responsibility per class.
- Separate orchestration from business logic.
- **Speculative interfaces:** Collapse project-owned `interface` types that have neither at least two non-test consumers nor at least two non-test implementations back into their concrete class. Test doubles, mocks, and fakes do not count toward either threshold. Implementing a framework or vendor interface (e.g. `ShouldQueue`, `HasLabel`, `Arrayable`) is always allowed. Keep a single-implementation, single-consumer project interface only when there is a documented architectural reason — a published package API surface or a plugin extension point with a written contract. See `@rules/php/core-standards.mdc` Design Principles.
- **Business Logic Layers (Laravel projects only):** Business logic must live in exactly one of the seven allowed class types — **Actions**, **Model Services**, **Repositories**, **ModelManagers**, **Data Validators**, **Data Builders**, or an **Eloquent model** (last one only for simple, self-contained own-data methods — see the boundary in `@rules/laravel/architecture.mdc` "Business Logic Layers"). When a class file contains business logic that spans more than one of these layers, contains business logic that does not fit any of them, or holds an Eloquent model method that crosses the simple-logic boundary (calls services / repositories / model managers, issues new queries, performs persistence side effects, or coordinates multiple entities), propose a refactoring that splits the responsibilities into dedicated classes from the seven-layer list. Surface every detected violation in the refactoring plan with the target layer for each extracted responsibility.
- Replace per-row DB queries inside loops with batch operations per `@rules/sql/optimalize.mdc` "Batch over per-row operations" — ModelManager `batchUpdate` / `batchInsert`, `whereIn(...)->delete()`, or a single bulk read keyed in memory. Keep per-row work only when an explicit side-effect dependency between iterations cannot be batched.
- Remove duplication (DRY).
- Before modifying code, enumerate every place that modifies data before it is saved or passed downstream (DTO mapping, payload shaping, key renaming, default fallbacks, format normalization, business-driven derivation). Surface the list in the refactoring plan and consolidate duplicates into the canonical layer per `@rules/laravel/architecture.mdc` Data Modification (DRY) section (Data Builder, DTO named constructor, Data Validator, ModelManager, Repository).
- Prefer small, focused methods.
- Extract intention-revealing private methods when it improves clarity.
- Avoid deep nesting and complex conditionals.
- Keep method signatures clear and minimal.
- **Method parameter count (>4 → DTO):** when a method, function, closure, constructor, `__invoke()`, or other callable accepts more than 4 parameters, propose extracting a dedicated typed DTO (preferably `final readonly` with promoted constructor properties) and passing it as a single argument, per `@rules/php/core-standards.mdc` Structure section. Promoted constructor properties count as parameters; a variadic (`...$args`) counts as one. The only exempt cases are entry-point signatures fixed by an external framework / vendor contract that the project cannot change (controller actions resolved by the service container, magic methods bound by the framework, event listeners whose signature is dictated by the dispatched event, methods overriding a parent / interface owned by `vendor/`); cite the exemption explicitly in the proposal with the parent class / interface and a one-line reason.

---

## Laravel Context (if applicable)

- Delegate business logic to Actions and Services.
- Do not place business logic in controllers or Livewire components.
- Use existing query scopes instead of duplicating conditions.
- Prefer DTOs over raw arrays when the project uses them.
- Keep Repositories limited to basic, reusable queries. When refactoring uncovers a feature-specific query method on a Repository, move it to a Service (single-model) or an Action (cross-model / cross-feature) that composes basic Repository methods (see `@rules/laravel/architecture.mdc` Repositories and ModelManagers section).

---

## Testing

- The **Test Coverage Gate** in the Execution section is the binding rule — pre-existing target lines must be at 100% coverage *before* the refactor, written into a dedicated `test(scope): cover <area> before refactor` commit per `@rules/refactoring/general.mdc` Test Coverage Contract.
- Inside the refactor commit, pre-existing tests **must remain unchanged** (mechanical-rename exemption only, flagged in the commit body). Editing the safety net inside the structural change voids the behavior-preservation proof.
- New tests covering code paths introduced by the refactor go in a separate `test(scope): …` commit *after* the refactor.
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
