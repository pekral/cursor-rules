---
name: rewrite-tests-pest
description: "Use when rewriting existing tests to PEST syntax. Follows project conventions, ensures DRY principles, uses data providers, maintains 100% coverage, and verifies test functionality."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/testing-conventions.mdc

**Steps:**
- For tests that do not use PEST syntax, I want you to rewrite them in PEST syntax.
- Never generate the covers() method!
- Follow the rules for writing tests.
- Create deterministic every time!
- Arrange-act-assert pattern, error cases first
- Before writing tests, always analyze the abstractions that will be used in the tests and always use helper methods if it simplifies the code.
- If there are any "shared" helper functions such as `bindSparkpostMailerNever($this->app);`, I want all these functions to be defined in the Pest.php file.
- If the PEST test requires calling a method that is in an abstract class, use the notation `test()->methodName()`.
- In tests, avoid reflection; use mocks instead (even partial ones, if they are effective and easy to read).
- Tests must not contain conditions (e.g., `if`, `switch`); split conditional logic into separate test cases instead.
- Correct DRY, use data providers, and try to write tests as simply as possible.
- After creating or modifying tests, check that they are not flaky.
- Analyze the created tests and all tests that are similar and can be simplified using data providers, then modify them.
- Tests must have 100% coverage.
- After writing the tests, verify that they are functional and follow the rules.

**After completing the tasks**
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
