# Test Coverage Policy

## Coverage Requirement

All changes in the current branch must have **100% test coverage**. No exceptions.

## Coverage Workflow

1. Analyze code coverage for all changes in the current branch.
2. Identify any lines, branches, or paths not covered by existing tests.
3. Add missing tests to ensure full coverage.
4. Write missing tests in a **separate commit** from the implementation.

## Code Quality in Tests

- Fix DRY violations in tests.
- Simplify the code base so that it is easy to read for humans.
- Keep tests as simple as possible.

## Testing Conventions

Apply `@rules/testing-conventions.mdc` for all test code.

## Bug-Specific Testing

For bugs, the TDD workflow in `references/task-classification.md` takes priority — the failing test must be written first and confirmed red before any fix.
