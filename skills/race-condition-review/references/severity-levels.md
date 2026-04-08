# Severity Levels

## Definitions

| Severity | Definition | Example |
|---|---|---|
| **Critical** | Exploitable race that can cause data corruption, double-spend, duplicate records, or incorrect balances under realistic load. | RMW without lock on a balance field; `firstOrCreate` without unique index |
| **Moderate** | Pattern that is unsafe under concurrent load but requires specific timing or volume to manifest; should be fixed before production scaling. | Lock without transaction; nested transactions without rollback |
| **Minor** | Defensive improvement or missing test coverage that reduces confidence without a direct exploit path. | No concurrent integration tests; missing idempotency key on low-frequency job |

## Classification rules

- If a race condition can be triggered by normal user behavior (e.g., double-clicking a button, two users editing the same record), classify as **Critical**.
- If a race condition requires high concurrency or unusual timing to manifest, classify as **Moderate**.
- If the issue is about missing safeguards or test coverage rather than a concrete race, classify as **Minor**.
- When in doubt, classify one level higher rather than lower.

## Job and queue patterns

- Re-dispatched or retried jobs that can process the same record multiple times without idempotency guards: **Critical**.
- Missing `ShouldBeUnique` or equivalent deduplication on jobs that mutate shared state: **Moderate** if idempotency is handled elsewhere, **Critical** if not.
- `$tries`, `$backoff`, and `$timeout` configured without idempotency guards: amplifies race conditions, flag severity based on the underlying RMW pattern.
