# TDD Workflow for Bugsnag Issues

## Principle

Bugsnag issues always represent runtime errors or exceptions and are therefore always treated as bugs. Every fix must follow strict Test-Driven Development.

## Steps

1. **Write a failing test** — Create a test that reproduces the reported failure. The test must fail before any fix is applied.
2. **Confirm the red failure** — Run the test and verify it fails. Do not proceed until you see the red failure output.
3. **Implement the minimal fix** — Write only the code necessary to make the test pass. Avoid unrelated changes.
4. **Confirm the green pass** — Run the test again and verify it passes.

## Rules

- Never skip the red-green cycle. A fix without a prior failing test is not valid.
- The reproducing test must exercise the exact error path reported in Bugsnag (same exception type, same call site if possible).
- If the bug cannot be reproduced in a test, document why and escalate before proceeding with a fix.
- After the fix, ensure 100% coverage of all changed lines. Add additional tests if needed.

## Coverage Requirement

- For all changes in the current branch, analyze code coverage and ensure that all changes are covered by tests.
- Add any missing tests to ensure 100% coverage of the changed code.
- Coverage gaps in changed files are treated as blockers — do not create a PR until coverage is complete.
