# Task Classification

## Classification Rules

Classify the task type before writing any code:

| Type | Signals | Examples |
|---|---|---|
| **Bug** | Existing functionality behaves incorrectly | Wrong output, exception, regression, data corruption |
| **Feature** | New behaviour that does not exist yet | New endpoint, new UI element, new integration |

### Label signals

Labels such as `bug`, `fix`, or `regression` are strong signals for bug classification.

### Default

If the classification is unclear, treat the task as a **feature**.

## TDD Workflow for Bugs

When the task is classified as a **bug**, follow strict TDD:

1. Write a test that reproduces the reported failure (the test must fail before any fix is applied).
2. Run the test and confirm it fails — do not proceed until you see the red failure.
3. Implement the minimal fix that makes the test pass.
4. Run the test again and confirm it is green.

## Feature Workflow

When the task is classified as a **feature**, implement it directly without the failing-test-first requirement. Tests are still mandatory but are written alongside or after implementation.
