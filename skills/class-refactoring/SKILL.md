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

**Steps:**
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
- Spatie DTOs instead of arrays (except Job constructors).
- **`?array` is forbidden:** Any use of `?array` as a type hint must be replaced with a typed collection, DTO, or explicit `array<Type>|null`. Vague nullable arrays hide structure and break static analysis.
- Laravel helpers over native PHP when appropriate.
- DRY principle — eliminate duplicates.
- **Validation rules as traits:** Extract reusable validation rules into traits in `App\Concerns` (e.g. `PasswordValidationRules`). Use these traits in FormRequest classes instead of duplicating rule arrays.
- Remove obvious comments; keep PHPStan-relevant docs.
- Single Responsibility Principle.
- Extract private methods if body exceeds ~30 lines.
- No single-use variables.
- Extract intention-revealing private methods
- **All business logic is allowed only in classes that follow the action pattern!**
- **Invokeable call convention:** When calling Action classes, always use direct invocation `$action($params)` — never `$action->__invoke($params)`.
- **Action pattern (only when `vendor/pekral/arch-app-services` exists):** Apply @.cursor/skills/refactor-entry-point-to-action/SKILL.md when the refactored class is a controller, job, command, or listener that contains orchestration logic.
- **Data Validator extraction (only when `vendor/pekral/arch-app-services` exists):** If an Action class contains inline validation logic (throwing `ValidationException` directly or calling `Validator::make()`), extract it into a dedicated Data Validator class under `app/DataValidators/{Domain}/`.
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
