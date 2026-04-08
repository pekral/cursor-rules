# TDD Applicability

## When to use TDD

- New features
- Bug fixes (write a test reproducing the bug first)
- Behavior changes
- Refactoring (ensure tests exist before changing code)

## Exceptions (confirm with user first)

- Throwaway prototypes
- Generated code
- Configuration files

## Bug-Fix Workflow

1. Write a failing test that reproduces the bug.
2. Verify the test fails for the expected reason.
3. Fix the bug with minimal code.
4. Verify the test passes.
5. Refactor if needed.

Never fix bugs without a failing test first.
