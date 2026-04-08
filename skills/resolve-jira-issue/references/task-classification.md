# Task Classification

## How to classify the task type

Classify the task type **before writing any code**.

### Bug

The issue describes existing functionality that behaves incorrectly (e.g., wrong output, exception, regression, data corruption).

**Signals:**
- Issue types such as `Bug` or `Defect`
- Labels such as `bug`, `fix`, or `regression`

### Feature

The issue requests new behaviour that does not exist yet.

### Ambiguous cases

If the classification is unclear, treat the task as a feature.

## TDD workflow for bugs

If the task is a **bug**, follow strict TDD:

1. Write a test that reproduces the reported failure (the test must fail before any fix is applied).
2. Run the test and confirm it fails — do not proceed until you see the red failure.
3. Implement the minimal fix that makes the test pass.
4. Run the test again and confirm it is green.

## Feature workflow

If the task is a **feature**, implement it directly without the failing-test-first requirement.
