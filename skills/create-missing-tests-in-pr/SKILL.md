---
name: create-missing-tests-in-pr
description: "Reads your pull request code review, verifies that all recommended test coverage is implemented in the codebase, and adds missing tests using the create-test skill. Use when a PR review already exists and missing tests must be completed with 100% coverage for current changes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Apply @rules/testing-conventions.mdc
- If you are not on the main git branch in the project, switch to it.
- This task is based on the existing pull request review.
- First read your existing code review for the current pull request and identify all testing recommendations related to current changes.
- Never change the assignment scope.
- Only add or modify tests when needed.
- Production code may only be changed if it is strictly required by the existing create-test skill or test infrastructure, otherwise do not modify it.
- Use @skills/create-test/SKILL.md for all test-writing work.

**Scripts:** Use the pre-built scripts in `@skills/create-missing-tests-in-pr/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/pr-review-comments.sh <PR>` | Fetch all review comments and inline threads |
| `scripts/pr-diff.sh <PR>` | Fetch the full PR diff |
| `scripts/pr-changed-files.sh <PR>` | List files changed in the PR with addition/deletion counts |

**References:**
- `references/review-extraction.md` — how to extract and classify test recommendations from PR reviews
- `references/test-quality-rules.md` — determinism, no conditions, mocking over reflection, data providers, project conventions
- `references/coverage-verification.md` — 100% coverage target, duplicate avoidance, running tests, production code policy

**Examples:** See `examples/` for expected output format:
- `examples/report-all-covered.md` — all review recommendations already satisfied
- `examples/report-tests-added.md` — tests were added or updated to fill gaps
- `examples/report-blocked.md` — blocker prevented full completion

**Steps:**

1. Load the current pull request context using GitHub CLI (`gh`) first. If `gh` is not available, use a GitHub MCP server. If neither is available, stop and return a failed result about missing GitHub tools.
2. Run `scripts/pr-review-comments.sh <PR>` to load all review comments.
3. Extract all recommendations related to missing tests, missing scenarios, edge cases, regression coverage, and coverage gaps per `references/review-extraction.md`.
4. Run `scripts/pr-diff.sh <PR>` and `scripts/pr-changed-files.sh <PR>` to analyze the current branch changes against the review findings.
5. Verify whether the recommended tests already exist in the codebase. If the review recommendation is already satisfied by existing tests, do not duplicate test coverage.
6. Check whether current changes have 100% coverage per `references/coverage-verification.md`.
7. If coverage is incomplete or recommended test scenarios are missing, use @skills/create-test/SKILL.md to write tests following `references/test-quality-rules.md`.
8. After adding or updating tests, run only the necessary tests for the current changes.
9. If coverage tooling exists, verify that current changes are covered with 100% coverage.
10. If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
11. Ask for a new commit with missing tests.

**Output contract:** For each evaluated PR, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Review recommendations | Yes | Table of all testing recommendations extracted from the review |
| Recommendation status | Yes | Per recommendation: `Already covered`, `Added`, or `Blocked` |
| Tests added or updated | Yes | List of test files and methods added or modified |
| Coverage result | Yes | 100% confirmed / partial with percentage |
| Blockers | If any | Description of what prevented full completion |
| Confidence notes | If applicable | Caveats or assumptions (e.g., no coverage tool available, inferred intent) |
| Next action | Yes | What should happen next (commit, manual review, fix blocker) |

**After completing the tasks:**

- Summarize what testing recommendations from the code review were verified.
- List added or modified test files.
- Confirm whether current changes now meet the required test coverage.
- If something is still missing, clearly describe the blocker or uncovered scenario.
