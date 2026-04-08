# I/O and Race Condition Signal Detection

## Race Condition Signals

Apply `@skills/race-condition-review/SKILL.md` when changes contain any of:

- Read-modify-write sequences
- Shared counters/balances/stock/quotas
- `firstOrCreate` / `updateOrCreate`
- Retried or re-dispatched jobs that mutate shared records
- Cache write-back patterns
- Bulk read-then-write operations

If none of these signals are present, skip the race condition review.

## I/O Bottleneck Signals

Flag each occurrence and recommend the appropriate async/streaming pattern when changes include any of:

- Synchronous file reads/writes on large or unbounded files
- Blocking HTTP calls without timeouts
- Storage operations executed in the request lifecycle
- Large file responses not streamed
- Export/import operations loading all records into memory

If none of these signals are present, skip the I/O bottleneck review.
