---
name: rewrite-tests-pest
description: "Use when rewriting existing tests to PEST syntax. Follows project conventions, ensures DRY principles, uses data providers, maintains 100% coverage, and verifies test functionality."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.claude/rules/.*mdc).

**Steps:**
- For tests that do not use PEST syntax, I want you to rewrite them in PEST syntax.
- Never generate the covers() method!
- Follow the rules for writing tests.
- Create deterministic every time!
- Arrange-act-assert pattern, error cases first
- Before writing tests, always analyze the abstractions that will be used in the tests and always use helper methods if it simplifies the code.
- **Never use the `describe()` function** in tests. Write tests at the top level using `it()` / `test()` only; do not wrap them in `describe()` blocks.
- If there are any "shared" helper functions such as `bindSparkpostMailerNever($this->app);`, I want all these functions to be defined in the Pest.php file.
- If the PEST test requires calling a method that is in an abstract class, use the notation `test()->methodName()`.
- Test classes must be `final`; use only local variables inside tests.
- Remove unnecessary mocks.
- Mock only external API communication services or if you need to simulate exceptions. Do not use constructor mocking!
- In tests, avoid reflection; use mocks instead (even partial ones, if they are effective and easy to read).
- If the test requires persisted Laravel Eloquent rows, create them only via `Model::factory()` (see `@.claude/rules/laravel/architecture.mdc` Testing). For other test data, follow `@.claude/rules/php/standards.mdc`. Never mock it or circumvent this in any other way!
- In Laravel factories, do not set attributes whose values are already defined by a database column default unless the test explicitly needs a different value (see `@.claude/rules/laravel/architecture.mdc` Schema defaults and Testing).
- In Laravel tests, dispatch queue jobs only via `JobClass::dispatch(...)` (see `@.claude/rules/laravel/architecture.mdc` Testing — Dispatching jobs in tests).
- Tests must not contain conditions (e.g., `if`, `switch`); split conditional logic into separate test cases instead.
- Correct DRY, use data providers, and try to write tests as simply as possible.
- After creating or modifying tests, check that they are not flaky.
- Analyze the created tests and all tests that are similar and can be simplified using data providers, then modify them.
- Tests must have 100% coverage.
- If new database migrations exist in the current branch, run them (`php artisan migrate`) before running tests.
- After writing the tests, verify that they are functional and follow the rules.
- After generating or modifying tests, verify that all new tests comply with the testing rules in `@.claude/rules/php/standards.mdc`. Check mock usage specifically: mock only external services (HTTP clients) or to simulate exceptions; remove any constructor mocks, unnecessary mocks, or mocks that can be replaced with real service logic.

**After completing the tasks**
- If according to @.claude/skills/test-like-human/SKILL.md the changes can be tested, do it!
