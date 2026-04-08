# Red Flags and Anti-Patterns

## Common rationalizations to reject

| Excuse | Reality |
|--------|---------|
| "Too simple to test" | Simple code breaks. The test takes seconds. |
| "I'll test after" | Tests passing immediately prove nothing. |
| "Need to explore first" | Fine. Throw away exploration, start with TDD. |
| "Test is hard to write" | Hard to test means hard to use. Simplify the design. |
| "TDD will slow me down" | TDD is faster than debugging in production. |
| "Already manually tested" | Ad-hoc testing is not systematic. No record, cannot re-run. |

## Red flags -- stop and start over

- Production code written before a failing test.
- Test passes immediately without implementation.
- Cannot explain why the test failed.
- Rationalizing "just this once".

## Testing anti-patterns to avoid

- Testing mock behavior instead of real behavior.
- Adding test-only methods to production classes -- use test utilities instead.
- Mocking without understanding the dependency chain -- understand side effects first, mock minimally.
- Incomplete mocks -- mirror real API response structure completely.
- Over-complex mock setup (more than 50% of the test) -- consider integration tests.
