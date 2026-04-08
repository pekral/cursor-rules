---
name: rewrite-tests-pest
description: "Use when rewriting existing tests to PEST syntax. Follows project conventions, ensures DRY principles, uses data providers, maintains 100% coverage, and verifies test functionality."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/testing-conventions.mdc

**References:**
- `references/pest-syntax-rules.md` — PEST syntax conventions, arrange-act-assert pattern, mocking, shared helpers
- `references/dry-and-data-providers.md` — DRY principles, data provider usage and refactoring
- `references/coverage-and-quality.md` — 100% coverage requirement, flakiness checks, post-completion verification

**Examples:** See `examples/` for expected rewrite patterns:
- `examples/rewrite-before-after.md` — PHPUnit to PEST conversion
- `examples/data-provider-usage.md` — collapsing repetitive tests into data providers
- `examples/shared-helpers.md` — extracting shared helpers into Pest.php

**Steps:**
1. Analyze the existing test file and the code under test. Identify abstractions, shared setup, and helper methods that can simplify the rewrite.
2. Rewrite all tests into PEST syntax per `references/pest-syntax-rules.md`:
   - Use arrange-act-assert pattern with error cases first.
   - Use `test()->methodName()` for abstract class methods.
   - Move shared helper functions to `Pest.php`.
   - Never generate the `covers()` method.
   - Avoid reflection; use mocks (including partial mocks) instead.
   - Tests must not contain conditions (`if`, `switch`); split into separate test cases.
3. Apply DRY principles per `references/dry-and-data-providers.md`:
   - Use data providers to simplify repetitive test cases.
   - Analyze all similar tests and refactor where possible.
4. Verify quality per `references/coverage-and-quality.md`:
   - Ensure 100% coverage of the code under test.
   - Confirm tests are not flaky and all pass.
   - Validate tests follow project test-writing guidelines.
5. If according to `@skills/test-like-human/SKILL.md` the changes can be tested, run those tests.

**Output contract:** After completing the rewrite, produce a structured report:

| Field | Required | Description |
|---|---|---|
| Files rewritten | Yes | List of test files converted to PEST syntax |
| Helpers extracted | If any | Shared functions moved to `Pest.php` |
| Data providers added | If any | Test cases collapsed into data providers |
| Coverage status | Yes | Confirmed 100% or gaps identified |
| Tests passing | Yes | All green / failures listed |
| Confidence notes | If applicable | Caveats (e.g., partial mock trade-offs, untestable paths) |
