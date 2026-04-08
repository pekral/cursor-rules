# Test Writing Rules

## Structure
- Use Arrange-Act-Assert pattern
- Place error cases first
- Tests must not contain conditions (`if`, `switch`); split conditional logic into separate test cases

## Mocking
- Mock only external services or exception scenarios
- Avoid reflection in tests; use mocks instead (including partial mocks if effective and readable)

## Data Providers
- Use data providers when they simplify writing and readability
- Analyze created tests and similar existing tests that can be simplified using data providers

## Helpers and Abstractions
- Before writing tests, analyze the abstractions that will be used
- Always use helper methods if they simplify the code
- Use existing test patterns, helpers, and conventions

## Determinism
- Every test must be deterministic — no randomness, no time-dependence, no order-dependence
- After creating or modifying tests, verify they are not flaky
