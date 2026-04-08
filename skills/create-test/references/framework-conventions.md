# Framework Conventions

## PEST (PHP)
- If a test requires calling a method from an abstract class, use `test()->methodName()` notation
- Never generate the `covers()` method

## Livewire
- In Livewire component tests, prefer explicit `set()` calls for form state updates over `fill()`
- `fill()` can trigger multiple Livewire round-trips (one per field) and significantly slow down tests

## General
- Follow project conventions for test file location and naming
- Use the project's existing test framework and tooling
- Locate existing tests before creating new ones
