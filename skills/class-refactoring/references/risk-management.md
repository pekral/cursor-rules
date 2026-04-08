# Risk Management in Refactoring

## Avoiding Regressions
- Run existing tests before and after each change.
- If tests do not cover the refactored code, write them first.
- Never refactor and add features in the same step.
- Check all callers of changed methods — verify they still work.

## When Refactoring Is Risky
- The class has no tests or low coverage.
- The class is used across many parts of the codebase.
- The change affects public API signatures.
- The class handles financial, security, or critical business logic.
- The change touches shared infrastructure (base classes, traits, interfaces).

## How to Validate Safely
- Compare method outputs before and after the change.
- Check side effects (database writes, events, jobs dispatched).
- Run tests for the changed file and all direct consumers.
- Review the diff for unintended behavioral changes.

## Rollback Strategy
- Each refactoring step should be a separate commit.
- If a step introduces a regression, revert only that commit.
- Keep the branch rebased on the latest main to avoid merge conflicts.
- Do not squash during development — squash only on merge.

## Risk Levels
- **Low risk:** rename private method, extract local variable, reorder methods.
- **Medium risk:** extract method to new class, change method signature, introduce DTO.
- **High risk:** change public API, modify shared base class, alter database queries.
- Start with low-risk changes, validate, then proceed to higher risk.
