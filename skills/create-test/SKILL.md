---
name: create-test
description: "Use when creating tests following project conventions and patterns. Ensures deterministic tests, 100% code coverage for changes, uses data providers where appropriate, and mocks only external services or exception scenarios."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/skills/base-constraints.mdc
- Apply @rules/skills/testing-conventions.mdc

**Steps:**
- Locate existing tests or create new ones following project conventions.
- Never modify production code!
- Create deterministic every time!
- Use existing test patterns, helpers, and conventions.
- Arrange-act-assert pattern, error cases first
- Before writing tests, always analyze the abstractions that will be used in the tests and always use helper methods if it simplifies the code.
- If the PEST test requires calling a method that is in an abstract class, use the notation `test()->methodName()`.
- Never generate the covers() method!
- In tests, avoid reflection; use mocks instead (even partial ones, if they are effective and easy to read).
- In Livewire component tests, prefer explicit `set()` calls for form state updates over `fill()`. `fill()` can trigger multiple Livewire round-trips (one per field) and significantly slow down tests.
- Tests must not contain conditions (e.g., `if`, `switch`); split conditional logic into separate test cases instead.
- Use data providers when they simplify writing and readability.
- Analyze the created tests and all tests that are similar and can be simplified using data providers, then modify them.
- Make sure of 100% coverage required for changes. Add tests so that 100% coverage is achieved. Prioritize modifying existing test cases; if tests do not exist, add them according to the valid rules for writing tests.
- After creating or modifying tests, check that they are not flaky.
- Remove generated coverage after work is done.

**After completing the tasks**
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
