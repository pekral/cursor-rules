---
name: test-driven-development
description: "Use when implementing any feature or bugfix using TDD methodology. Enforces red-green-refactor cycle, ensures failing test exists before any production code, prevents test-after anti-patterns, and maintains 100% coverage for changes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/testing-conventions.mdc
- All tests must follow the conventions defined in @skills/create-test/SKILL.md.

**Core principle:** If you did not watch the test fail, you do not know if it tests the right thing.

**Iron Law:**
```
NO PRODUCTION CODE WITHOUT A FAILING TEST FIRST
```

Write code before the test? Delete it. Start over. No exceptions.

**Scripts:** Use the pre-built scripts in `@skills/test-driven-development/scripts/` for test execution. Do not reinvent these commands -- run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/run-test.sh` | Run a single test file or filter by name |
| `scripts/run-coverage.sh` | Run tests with code coverage report |

**References:**
- `references/test-writing-rules.md` -- rules for writing tests: one behavior per test, mocking guidelines, prohibited patterns
- `references/red-flags-and-anti-patterns.md` -- common rationalizations, red flags, testing anti-patterns
- `references/verification-checklist.md` -- checklist to complete before marking work done
- `references/tdd-applicability.md` -- when to use TDD, exceptions, bug-fix workflow

**Examples:** See `examples/` for expected output format:
- `examples/red-green-refactor-cycle.md` -- full TDD cycle for a new feature
- `examples/bug-fix-workflow.md` -- TDD-driven bug fix with summary report

**Steps:**

## 1. RED - Write Failing Test

Write one minimal test showing expected behavior per `references/test-writing-rules.md`.

```php
it('rejects empty email on registration', function (): void {
    $response = $this->postJson('/api/register', [
        'email' => '',
        'password' => 'SecurePass123!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
```

## 2. Verify RED - Watch It Fail

**Mandatory. Never skip.**

Run `scripts/run-test.sh --filter "test name"` and confirm:
- Test fails (not errors from syntax or missing imports).
- Failure message matches expected behavior (feature missing, not typos).
- If test passes immediately -- you are testing existing behavior. Fix the test.

## 3. GREEN - Minimal Code

Write the simplest code to make the test pass.

- Do not add features beyond what the test requires.
- Do not refactor other code.
- Do not over-engineer with unnecessary options or abstractions.

## 4. Verify GREEN - Watch It Pass

**Mandatory.**

Run `scripts/run-test.sh --filter "test name"` and confirm:
- The new test passes.
- All other tests still pass.
- No errors or warnings in output.
- If the test fails -- fix the code, not the test.

## 5. REFACTOR - Clean Up

After green only:
- Remove duplication.
- Improve names.
- Extract helpers or private methods.
- Keep tests green throughout -- do not add new behavior during refactoring.

## 6. Repeat

Next failing test for the next behavior. Continue the cycle.

When to use TDD and exceptions: see `references/tdd-applicability.md`.

For bug fixes, follow the bug-fix workflow in `references/tdd-applicability.md` and see `examples/bug-fix-workflow.md` for a concrete example.

If any red flags or rationalizations arise, consult `references/red-flags-and-anti-patterns.md`.

**Output contract:** For each TDD cycle completed, the output must include:

| Field | Required | Description |
|---|---|---|
| Test name | Yes | Descriptive name of the test |
| RED result | Yes | Confirmation test failed and failure reason |
| GREEN result | Yes | Confirmation test passes after implementation |
| Implementation summary | Yes | What production code was written or changed |
| Refactoring notes | If applicable | What was cleaned up after green |
| Coverage | Yes | 100% for changed code confirmed |
| Confidence notes | If applicable | Caveats or assumptions (e.g., partial mock limitations, skipped edge case) |
| All tests pass | Yes | Confirmation full suite is green |

**After completing the tasks:**
- Run `references/verification-checklist.md` to verify all items are satisfied.
- Check that the tests are written according to the test-writing guidelines and ensure 100% coverage; fix dry; use data providers to simplify the tests.
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
