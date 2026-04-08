# Test Coverage Policy

## Coverage requirement

For all changes in the current branch, analyze code coverage and ensure that all changes are covered by tests. Add any missing tests to ensure **100% coverage**.

## Testing conventions

- Apply `@rules/testing-conventions.mdc` for all tests.
- Write missing tests for current changes in a **separate commit**.
- Fix DRY violations and simplify the code base so it is easy to read for humans and as simple as possible.

## CI and fixers

- If there are any automatic fixers in the project that are called through another layer (such as Phing or composer scripts), run them and ensure automatic error correction.
- Find and load local configs for tools if they exist.
- If there are any CI (or local) checkers, run them.
- **Never run all tests for the entire codebase** — only for the current changes.
- Fix any errors, run the fixers again, and keep fixing until all errors are resolved.
- Never try to format PHP code outside of the project fixers.

## Action-pattern refactors

During issue resolution: if an Action calls a Service or Facade method that is used only once in the entire codebase, move the business logic from that Service/Facade method directly into the Action and remove the original Service/Facade method.
