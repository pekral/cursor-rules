---
name: test-driven-development
description: "Use when implementing any feature or bugfix using TDD methodology. Enforces red-green-refactor cycle, ensures failing test exists before any production code, prevents test-after anti-patterns, and maintains 100% coverage for changes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- The generated code must comply with all rules defined for writing tests in @.cursor/rules/php/standards.mdc. If the project is written in Laravel, it must also comply with @.cursor/rules/laravel/architecture.mdc.
- All tests must follow the conventions defined in @.cursor/skills/create-test/SKILL.md.
- **Never use the `describe()` function** in tests. Write tests at the top level using `it()` / `test()` only.
- If new database migrations exist in the current branch, run them (`php artisan migrate`) before running tests.

**Core principle:** If you did not watch the test fail, you do not know if it tests the right thing.

**Iron Law:**
```
NO PRODUCTION CODE WITHOUT A FAILING TEST FIRST
```

Write code before the test? Delete it. Start over. No exceptions.

**Steps:**

## 1. RED - Write Failing Test

Write one minimal test showing expected behavior.

- One behavior per test.
- Clear, descriptive name that describes the behavior.
- Real code paths — mock only external services (HTTP clients) or to simulate exceptions. Do not use constructor mocking!
- Arrange-act-assert pattern, error cases first.
- Test classes must be `final`; use only local variables inside tests.
- In tests, avoid reflection; use mocks instead (even partial ones, if they are effective and easy to read).
- If the test requires persisted Laravel Eloquent rows, create them only via `Model::factory()` (see `@.cursor/rules/laravel/architecture.mdc` Testing). For other test data, follow `@.cursor/rules/php/standards.mdc`. Never mock it or circumvent this in any other way!
- In Laravel factories, do not set attributes whose values are already defined by a database column default unless the test explicitly needs a different value (see `@.cursor/rules/laravel/architecture.mdc` Schema defaults and Testing).
- In Laravel tests, dispatch queue jobs only via `JobClass::dispatch(...)` (see `@.cursor/rules/laravel/architecture.mdc` Testing — Dispatching jobs in tests).
- In Livewire component tests, prefer `set()` for form state updates instead of `fill()` to avoid one round-trip per field and keep the suite fast.
- Tests must not contain conditions (e.g., `if`, `switch`); split conditional logic into separate test cases instead.
- Use data providers when they simplify writing and readability.
- Never generate the `covers()` method.

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

Run the test and confirm:
- Test fails (not errors from syntax or missing imports).
- Failure message matches expected behavior (feature missing, not typos).
- If test passes immediately — you are testing existing behavior. Fix the test.

## 3. GREEN - Minimal Code

Write the simplest code to make the test pass.

- Do not add features beyond what the test requires.
- Do not refactor other code.
- Do not over-engineer with unnecessary options or abstractions.

## 4. Verify GREEN - Watch It Pass

**Mandatory.**

Run the test and confirm:
- The new test passes.
- All other tests still pass.
- No errors or warnings in output.
- If the test fails — fix the code, not the test.

## 5. REFACTOR - Clean Up

After green only:
- Remove duplication.
- Improve names.
- Extract helpers or private methods.
- Keep tests green throughout — do not add new behavior during refactoring.

## 6. Repeat

Next failing test for the next behavior. Continue the cycle.

**When to use TDD:**
- New features
- Bug fixes (write a test reproducing the bug first)
- Behavior changes
- Refactoring (ensure tests exist before changing code)

**Exceptions (confirm with user first):**
- Throwaway prototypes
- Generated code
- Configuration files

**Bug-Fix Workflow:**
1. Write a failing test that reproduces the bug.
2. Verify the test fails for the expected reason.
3. Fix the bug with minimal code.
4. Verify the test passes.
5. Refactor if needed.

Never fix bugs without a failing test first.

**Common rationalizations to reject:**

| Excuse | Reality |
|--------|---------|
| "Too simple to test" | Simple code breaks. The test takes seconds. |
| "I'll test after" | Tests passing immediately prove nothing. |
| "Need to explore first" | Fine. Throw away exploration, start with TDD. |
| "Test is hard to write" | Hard to test means hard to use. Simplify the design. |
| "TDD will slow me down" | TDD is faster than debugging in production. |
| "Already manually tested" | Ad-hoc testing is not systematic. No record, cannot re-run. |

**Red flags — stop and start over:**
- Production code written before a failing test.
- Test passes immediately without implementation.
- Cannot explain why the test failed.
- Rationalizing "just this once".

**Testing anti-patterns to avoid:**
- Testing mock behavior instead of real behavior.
- Adding test-only methods to production classes — use test utilities instead.
- Mocking without understanding the dependency chain — understand side effects first, mock minimally.
- Incomplete mocks — mirror real API response structure completely.
- Over-complex mock setup (more than 50% of the test) — consider integration tests.

**Verification checklist before marking work complete:**
- [ ] Every new function or method has a test.
- [ ] Watched each test fail before implementing.
- [ ] Each test failed for the expected reason (feature missing, not typo).
- [ ] Wrote minimal code to pass each test.
- [ ] All tests pass.
- [ ] Tests use real code (mocks only for external services or exception simulation).
- [ ] Edge cases and error paths are covered.
- [ ] 100% code coverage for changes.
- [ ] Remove generated coverage files after verification.

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
