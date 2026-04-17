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
-   Apply @rules/php/core-standards.mdc
-   Apply @rules/git/general.mdc
-   Apply @rules/code-testing/general.mdc
-   If you are not on the main git branch in the project, switch to it.
-   This task is based on the existing pull request review.
-   First read your existing code review for the current pull request
    and identify all testing recommendations related to current changes.
-   Never change the assignment scope.
-   Only add or modify tests when needed.
-   Production code may only be changed if it is strictly required by
    the existing create-test skill or test infrastructure, otherwise do
    not modify it.
-   Use @skills/create-test/SKILL.md for all test-writing work.

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
    use @skills/create-test/SKILL.md.
-   Follow existing project test conventions, helpers, patterns, and
    abstractions.
-   Prefer updating existing tests first. Create new tests only if
    required.
-   Create deterministic every time!
-   Make sure tests are deterministic and not flaky.
-   In tests, avoid reflection; use mocks instead (even partial ones, if they are effective and easy to read).
-   Tests must not contain conditions (e.g., `if`, `switch`); split conditional logic into separate test cases instead.
-   Use data providers where they improve readability and simplify
    repeated test cases.
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

-   Run project fixers in dry-run mode on all changed test files and fix any violations.
-   Run a quick code review of all added or updated tests against `@rules/code-testing/general.mdc` and fix any findings.
-   Summarize what testing recommendations from the code review were
    verified.
-   List added or modified test files.
-   Confirm whether current changes now meet the required test coverage (must be 100%).
-   If something is still missing, clearly describe the blocker or
    uncovered scenario.
- Ask for create new commit with missing tests
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!

## Principles

- Follow code review recommendations strictly
- Do not duplicate existing tests
- Prefer minimal changes for full coverage
- Use data providers where they improve readability and reduce duplication
- Every test change must be verified to pass before moving on
- Focus on changed code only
