---
name: create-test
description: "Creates tests following project conventions and patterns. Ensures deterministic tests, 100% code coverage for changes, uses data providers where appropriate, and mocks only external services or exception scenarios."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.md file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).

**Steps:**
- Locate existing tests or create new ones following project conventions.
- Never modify production code!
- Create deterministic everytime!
- Use existing test patterns, helpers, and conventions.
- Arrange-act-assert pattern, error cases first
- Before writing tests, always analyze the abstractions that will be used in the tests and always use helper methods if it simplifies the code.
- **Never use the `describe()` function** in tests. Write tests at the top level using `it()` / `test()` only; do not wrap them in `describe()` blocks.
- If the PEST test requires calling a method that is in an abstract class, use the notation `test()->methodName()`.
- Never generate the covers() method!
- Remove unnecessary mocks.
- Mock only external API communication services or if you need simulate exceptions. Do not Constructor mocking!
- Use data providers when they simplify writing and readability.
- Analyze the created tests and all tests that are similar and can be simplified using data providers, then modify them. 
- Make sure of 100% coverage required for changes. Add tests so that 100% coverage is achieved. Prioritize modifying existing test cases; if tests do not exist, add them according to the valid rules for writing tests.
- After creating or modifying tests, check that they are not flaky.
- Remove generated coverage after work is done.
