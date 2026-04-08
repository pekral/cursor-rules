---
name: create-test
description: "Use when creating tests following project conventions and patterns. Ensures deterministic tests, 100% code coverage for changes, uses data providers where appropriate, and mocks only external services or exception scenarios."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/testing-conventions.mdc
- Never modify production code!
- Create deterministic tests every time!

**References:**
- `references/test-writing-rules.md` — structure, mocking, data providers, helpers, determinism
- `references/coverage-policy.md` — 100% coverage requirement, strategy, cleanup
- `references/framework-conventions.md` — PEST, Livewire, and general framework-specific rules

**Examples:** See `examples/` for expected output format:
- `examples/report-tests-created.md` — new tests created with full coverage
- `examples/report-coverage-gap.md` — coverage gap identified and filled
- `examples/report-data-provider-refactor.md` — existing tests simplified with data providers

**Steps:**
1. Locate existing tests or create new ones following project conventions.
2. Use existing test patterns, helpers, and conventions.
3. Before writing tests, analyze the abstractions that will be used and always use helper methods if they simplify the code.
4. Write tests following `references/test-writing-rules.md`:
   - Arrange-Act-Assert pattern, error cases first.
   - No conditions (`if`, `switch`) in tests; split into separate test cases.
   - Avoid reflection; use mocks instead (even partial ones, if effective and readable).
   - Use data providers when they simplify writing and readability.
5. Follow framework-specific rules per `references/framework-conventions.md`:
   - PEST: use `test()->methodName()` for abstract class methods; never generate `covers()`.
   - Livewire: prefer explicit `set()` over `fill()` for form state updates.
6. Ensure 100% coverage per `references/coverage-policy.md`:
   - Prioritize modifying existing test cases; add new ones only if needed.
   - Analyze similar tests that can be simplified using data providers, then modify them.
7. Check that tests are not flaky after creating or modifying them.
8. Remove generated coverage artifacts after work is done.

**Output contract:** For each test creation/modification task, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Target | Yes | File or class under test |
| Decision | Yes | `tests created`, `tests refactored`, or `additional tests needed` |
| Coverage | Yes | Coverage percentage for changed code (must be 100%) |
| Tests added | Yes | Count of new test cases |
| Tests modified | If applicable | Count of modified test cases |
| Data providers | If applicable | Count and names of data providers used |
| Flaky check | Yes | Passed / failed |
| Confidence notes | If applicable | Caveats or assumptions (e.g., untestable external dependency) |
| Next action | Yes | What should happen next |

**After completing the tasks:**
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
