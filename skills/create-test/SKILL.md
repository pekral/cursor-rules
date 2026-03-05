---
name: create-test
description: "Creates tests following project conventions and patterns. Ensures deterministic tests, 100% code coverage for changes, uses data providers where appropriate, and mocks only external services or exception scenarios. Use when adding or extending tests for new or changed code. Do not use for refactoring production code or for rewriting existing tests to another framework (use rewrite-tests-pest instead)."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Do not modify production code.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Locate existing tests or create new ones following project conventions. Create deterministic tests every time.
3. Use existing test patterns, helpers, and conventions. Apply arrange–act–assert; cover error cases first.
4. Before writing tests, analyze the abstractions used in the tests and use helper methods when they simplify the code.
5. Do not use the `describe()` function. Write tests at the top level with `it()` or `test()` only; do not wrap them in `describe()` blocks.
6. If a PEST test must call a method from an abstract class, use the notation `test()->methodName()`.
7. Do not generate the `covers()` method. Remove unnecessary mocks.
8. Mock only external API communication or when simulating exceptions. Do not use constructor mocking.
9. Use data providers when they simplify writing and readability.
10. Analyze created tests and similar tests that can be simplified with data providers; then update them.
11. Ensure 100% coverage for the changed code. Add or adjust tests until 100% coverage is achieved. Prefer modifying existing test cases; if none exist, add tests according to the project test rules.
12. After creating or modifying tests, verify they are not flaky.
13. Remove generated coverage artifacts after work is done.
