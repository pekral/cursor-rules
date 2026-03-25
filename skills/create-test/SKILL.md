---
name: create-test
description: "Use when creating tests following project conventions and patterns. Ensures deterministic tests, 100% code coverage for changes, uses data providers where appropriate, and mocks only external services or exception scenarios."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- The generated code must comply with all rules defined for writing tests in @.cursor/rules/php
/standards.mdc. If the project is written in Laravel, it must also comply with @.cursor/rules/laravel/architecture.mdc.

**Steps:**
- Locate existing tests or create new ones following project conventions.
- Never modify production code!
- Create deterministic every time!
- Use existing test patterns, helpers, and conventions.
- Arrange-act-assert pattern, error cases first
- Before writing tests, always analyze the abstractions that will be used in the tests and always use helper methods if it simplifies the code.
- **Never use the `describe()` function** in tests. Write tests at the top level using `it()` / `test()` only; do not wrap them in `describe()` blocks.
- If the PEST test requires calling a method that is in an abstract class, use the notation `test()->methodName()`.
- Never generate the covers() method!
- Test classes must be `final`; use only local variables inside tests.
- Remove unnecessary mocks.
- Mock only external API communication services or if you need to simulate exceptions. Do not use constructor mocking!
- In tests, avoid reflection; use mocks instead (even partial ones, if they are effective and easy to read).
- If the test requires persisted Laravel Eloquent rows, create them only via `Model::factory()` (see `@.cursor/rules/laravel/architecture.mdc` Testing). For other test data, follow `@.cursor/rules/php/standards.mdc`. Never mock it or circumvent this in any other way!
- Tests must not contain conditions (e.g., `if`, `switch`); split conditional logic into separate test cases instead.
- Use data providers when they simplify writing and readability.
- Analyze the created tests and all tests that are similar and can be simplified using data providers, then modify them.
- Make sure of 100% coverage required for changes. Add tests so that 100% coverage is achieved. Prioritize modifying existing test cases; if tests do not exist, add them according to the valid rules for writing tests.
- If new database migrations exist in the current branch, run them (`php artisan migrate`) before running tests.
- After creating or modifying tests, check that they are not flaky.
- After generating or modifying tests, verify that all new tests comply with the testing rules in `@.cursor/rules/php/standards.mdc`. Check mock usage specifically: mock only external services (HTTP clients) or to simulate exceptions; remove any constructor mocks, unnecessary mocks, or mocks that can be replaced with real service logic.
- Remove generated coverage after work is done.

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
