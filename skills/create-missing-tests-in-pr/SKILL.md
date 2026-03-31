---
name: create-missing-tests-in-pr
description: Reads your pull request code review, verifies that all
  recommended test coverage is implemented in the codebase, and adds
  missing tests using the create-test skill. Use when a PR review
  already exists and missing tests must be completed with 100% coverage
  for current changes.
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

**Constraint:**
-   For all GitHub operations, prefer GitHub CLI (`gh`) as the primary tool.
-   If `gh` is not available or cannot be used, use an available GitHub MCP server as fallback.
-   If neither `gh` nor a GitHub MCP server is available, stop and return a failed result explaining that required GitHub tools are missing.
-   Read project.mdc file
-   First, load all the rules for the cursor editor
    (.cursor/rules/.\*mdc).
-   I want the texts to be in the language in which the assignment was
    written.
-   If you are not on the main git branch in the project, switch to it.
-   This task is based on the existing pull request review.
-   First read your existing code review for the current pull request
    and identify all testing recommendations related to current changes.
-   Never change the assignment scope.
-   Only add or modify tests when needed.
-   Production code may only be changed if it is strictly required by
    the existing create-test skill or test infrastructure, otherwise do
    not modify it.
-   Use @.cursor/skills/create-test/SKILL.md for all test-writing work.

**Steps:**

-   Load the current pull request context using GitHub CLI (`gh`) first.
    If `gh` is not available, use a GitHub MCP server. If neither is
    available, stop and return a failed result about missing GitHub tools.
-   Read your existing code review for the pull request.
-   Extract all recommendations related to missing tests, missing
    scenarios, edge cases, regression coverage, and coverage gaps.
-   Analyze the current branch changes against the review findings.
-   Verify whether the recommended tests already exist in the codebase.
-   Check whether current changes have 100% coverage.
-   If coverage is incomplete or recommended test scenarios are missing,
    use @.cursor/skills/create-test/SKILL.md.
-   Follow existing project test conventions, helpers, patterns, and
    abstractions.
-   Prefer updating existing tests first. Create new tests only if
    required.
-   Create deterministic every time!
-   Make sure tests are deterministic and not flaky.
-   Never use describe() in tests.
-   Test classes must be `final`; use only local variables inside tests.
-   Mock only external services or exception scenarios. Do not use constructor mocking!
-   Remove unnecessary mocks if found while updating tests.
-   In tests, avoid reflection; use mocks instead (even partial ones, if they are effective and easy to read).
-   If the test requires persisted Laravel Eloquent rows, create them only via `Model::factory()` (see `@.cursor/rules/laravel/architecture.mdc` Testing). For other test data, follow `@.cursor/rules/php/standards.mdc`. Never mock it or circumvent this in any other way!
-   In Laravel factories, do not set attributes whose values are already defined by a database column default unless the test explicitly needs a different value (see `@.cursor/rules/laravel/architecture.mdc` Schema defaults and Testing).
-   In Laravel tests, dispatch queue jobs only via `JobClass::dispatch(...)` (see `@.cursor/rules/laravel/architecture.mdc` Testing — Dispatching jobs in tests).
-   Tests must not contain conditions (e.g., `if`, `switch`); split conditional logic into separate test cases instead.
-   After generating or modifying tests, verify that all new tests comply with the testing rules in `@.cursor/rules/php/standards.mdc`. Check mock usage specifically: mock only external services (HTTP clients) or to simulate exceptions; remove any constructor mocks, unnecessary mocks, or mocks that can be replaced with real service logic.
-   Use data providers where they improve readability and simplify
    repeated test cases.
-   If new database migrations exist in the current branch, run them (`php artisan migrate`) before running tests.
-   After adding or updating tests, run only the necessary tests for the
    current changes.
-   If coverage tooling exists, verify that current changes are covered
    with 100% coverage.
-   If fixers or test-related wrappers exist in the project, use them.
-   Do not run the whole test suite unless it is required for the
    changed files workflow.
-   If the review recommendation is already satisfied by existing tests,
    do not duplicate test coverage.

**Deliver:**

Provide a brief markdown summary including:

-   reviewed PR testing recommendations
-   which recommendations were already covered
-   which tests were added or updated
-   whether 100% coverage for current changes was achieved
-   any blocker preventing full completion

**After completing the tasks**

-   Summarize what testing recommendations from the code review were
    verified.
-   List added or modified test files.
-   Confirm whether current changes now meet the required test coverage.
-   If something is still missing, clearly describe the blocker or
    uncovered scenario.
- Ask for create new commit with missing tests
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
