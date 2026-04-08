# PEST Syntax Rules

## General

- Rewrite all tests that do not use PEST syntax into PEST syntax
- Never generate the `covers()` method
- Create deterministic tests every time
- Use `test()->methodName()` notation when the PEST test requires calling a method from an abstract class

## Structure

- Follow the **arrange-act-assert** pattern
- Place **error cases first**, then happy paths
- Tests must not contain conditions (`if`, `switch`) — split conditional logic into separate test cases

## Shared Helpers

- Shared helper functions (e.g., `bindSparkpostMailerNever($this->app)`) must be defined in the `Pest.php` file
- Before writing tests, analyze the abstractions used and always extract helper methods if they simplify the code

## Mocking

- Avoid reflection in tests; use mocks instead (including partial mocks if they are effective and easy to read)
