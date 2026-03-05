---
name: class-refactoring
description: "Refactors PHP classes following Laravel best practices and SOLID principles. Ensures code quality, maintains functionality, improves testability, and achieves 100% code coverage. Use when improving existing PHP/Laravel code structure. Do not use for writing new features from scratch or for non-PHP codebases."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Analyze the class and complete the TODO list tasks.
3. Preserve functionality — change how, not what. Focus on recently modified code unless instructed otherwise.
4. Do not increase the public API surface without strong justification.
5. Produce clean, modern, optimized code. Use stateless PHP classes. Prefer collections over `foreach` where appropriate.
6. Add PHPDoc for PHPStan analysis. Comment complex logic. Avoid magic numbers and deep nesting. Prefer small, focused functions. Use English comments only.
7. Use Spatie DTOs instead of arrays (except Job constructors). Use Laravel helpers over native PHP when appropriate.
8. Apply DRY — eliminate duplicates. Remove obvious comments; keep PHPStan-relevant docs.
9. Apply Single Responsibility Principle. Extract private methods if body exceeds ~30 lines. Avoid single-use variables. Extract intention-revealing private methods.
10. Separate orchestration layer from business logic. Split by responsibility. Centralize business rules. Do not duplicate business logic.
11. Keep method signatures expressive and minimal. Match test variable names to actual use cases.
12. Ensure new tests cover the relevant code. Verify code coverage after refactoring; add tests until 100% coverage for changes. Remove coverage artifacts after verification.
13. Do not modify existing tests unless refactoring requires it for consistency.

**Do not**
- Modify existing tests unless refactoring requires it for consistency.
