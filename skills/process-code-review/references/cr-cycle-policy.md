# Code Review Cycle Policy

## Pre-commit review gates

Before committing and pushing, the following reviews must pass:

1. Re-check current changes with @skills/code-review/SKILL.md and @skills/security-review/SKILL.md.
2. If review feedback requires additional tests, use @skills/create-missing-tests-in-pr/SKILL.md and ensure current changes are fully covered.
3. If new database migrations were created during the changes, run them (`php artisan migrate`) before running tests or creating a PR.
4. Run only checks/tests needed for the changed files and fix all errors before continuing.

## Issue-tracker-specific review

Run the issue-tracker-specific code review skill before PR creation:
- **GitHub issue flow:** run @skills/code-review-github/SKILL.md
- **JIRA issue flow:** run @skills/code-review-jira/SKILL.md

## Iterative resolution

- Fix all **Critical** and **Moderate** findings from the review and repeat the same review skill until no Critical or Moderate findings remain.
- Do not create a new PR before the CR cycle is clean.

## Human-like testing

- After the CR loop is clean (no **Critical** or **Moderate** findings), run @skills/test-like-human/SKILL.md when the change can be tested.
- The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.
