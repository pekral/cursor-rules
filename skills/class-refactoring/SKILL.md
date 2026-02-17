---
name: class-refactoring
description: "Refactors PHP classes following Laravel best practices and SOLID principles. Ensures code quality, maintains functionality, improves testability, and achieves 100% code coverage. Focuses on single responsibility, DRY principle, and clean code structure."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).

**Steps:**
- Analyze the class and complete the TODO list tasks.
- Verify code coverage after refactoring.
- Preserve functionality — change how, not what.
- Focus on recently modified code unless instructed otherwise.
- No increase in public API surface without strong justification
- Clean, modern, optimized code.
- Stateless PHP classes.
- Collections over `foreach` where appropriate.
- PHPDoc for PHPStan analysis.
- English comments only.
- Spatie DTOs instead of arrays (except Job constructors).
- Laravel helpers over native PHP when appropriate.
- DRY principle — eliminate duplicates.
- Remove obvious comments; keep PHPStan-relevant docs.
- Single Responsibility Principle.
- Extract private methods if body exceeds ~30 lines.
- No single-use variables.
- Extract intention-revealing private methods
- Separate orchestration layer from business logic
- Split by responsibility
- Centralize business rules
- Business logic duplication is not allowed.
- Method signatures must remain expressive and minimal.
- Match test variable names to actual use cases.
- New tests must cover relevant code.
- Remove coverage files after verification.

  **Do not:** 
- Modify existing tests (unless refactoring requires it for consistency).
