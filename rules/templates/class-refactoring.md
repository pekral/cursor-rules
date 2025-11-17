# Task Overview

**Your responsibilities:**
1. Review all rules defined in (real scan this path) `.cursor/rules/*.mdc`.
2. Apply these rules to the `ExampleClass::class`.
3. Analyze the class and complete all tasks from the defined TODO list.
4. After refactoring, verify and inspect the code coverage for this class.

---

# TODO Checklist for This Task

## Code Quality & Style
- Use clean, modern, and optimized code.
- Ensure all PHP classes remain stateless.
- Replace `foreach` loops with Laravel Collections where appropriate.
- Add missing PHPDoc annotations required for proper PHPStan analysis.
- Translate all comments into English.
- Use **Spatie DTOs** instead of arrays (if this package is available in this project) — except in Laravel Job constructors where DTOs must *not* be used.
- Use Laravel helper functions instead of native PHP functions when appropriate (see “Reasoning instructions”).

---

## Architecture & Best Practices
- Eliminate duplicate logic and follow the DRY principle.
- Remove unnecessary comments — keep only those explaining complex logic. (Do not remove PHPStan documentation.)
- Produce readable, simple, and clean code. Prioritize Laravel best practices.
- Follow the Single Responsibility Principle.
- If a method body exceeds roughly 30 lines, review and extract private methods when appropriate.
- Do not create a variable if it is used only once.

---

## Tests & PHPStan
- Review variable names in tests to ensure they match their actual use cases and values.
- Improve iterable shapes for PHPStan analysis where possible.
- **Do not modify any existing tests.**

---

## Project Maintenance Steps
- Make sure when new tests are added, they cover all relevant code.
- Remove the coverage file if it exists.
