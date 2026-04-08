# Test Quality Rules

## Determinism

- Tests must be deterministic — every run produces the same result.
- Avoid reliance on external state, time, randomness, or ordering.
- Make sure tests are not flaky.

## No Conditions in Tests

- Tests must not contain conditional logic (`if`, `switch`, ternary).
- Split conditional scenarios into separate, focused test cases.

## Mocking Over Reflection

- Avoid reflection in tests; use mocks instead (including partial mocks when effective and readable).
- Mocks should be explicit and minimal — mock only what is necessary.

## Data Providers

- Use data providers where they improve readability and reduce duplication across repeated test cases.
- Each data provider entry should have a descriptive key or label.

## Test Conventions

- Follow existing project test conventions, helpers, patterns, and abstractions.
- If fixers or test-related wrappers exist in the project, use them.
- Prefer updating existing test files over creating new ones unless a new file is clearly required.
