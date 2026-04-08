# Test Writing Rules

## One behavior per test
- Each test covers exactly one behavior or scenario
- Clear, descriptive name that describes the behavior being tested

## Real code paths
- Mock only external services (HTTP clients) or to simulate exceptions
- Do not use constructor mocking
- **Prefer partial mocks** (`Mockery::mock(Service::class)->makePartial()`) so real methods run and only needed methods are overridden

## Test structure
- Arrange-act-assert pattern, error cases first
- Tests must not contain conditions (e.g., `if`, `switch`); split conditional logic into separate test cases instead
- Use data providers when they simplify writing and readability

## Mocking guidelines
- In tests, avoid reflection; use mocks instead (even partial ones, if they are effective and easy to read)
- In Livewire component tests, prefer `set()` for form state updates instead of `fill()` to avoid one round-trip per field and keep the suite fast

## Prohibited patterns
- Never generate the `covers()` method
