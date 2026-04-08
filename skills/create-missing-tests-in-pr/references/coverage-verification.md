# Coverage Verification

## Coverage Target

- Current changes must reach **100% test coverage**.
- If coverage tooling exists in the project, run it to verify.

## What Counts as Covered

- Every new or modified code path has at least one test exercising it.
- Edge cases identified in the PR review are covered.
- Regression scenarios flagged in the review are covered.

## Avoiding Duplicate Coverage

- If a review recommendation is already satisfied by an existing test, do NOT add duplicate coverage.
- Before writing a new test, verify whether the scenario is already tested elsewhere in the codebase.

## Running Tests

- After adding or updating tests, run **only** the tests relevant to the current changes.
- Do NOT run the entire test suite unless the project workflow requires it for the changed files.

## Production Code Changes

- Production code may only be changed if it is **strictly required** by the create-test skill or test infrastructure.
- Never modify production code for convenience — only for testability when unavoidable.
