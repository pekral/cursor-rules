# Atomicity Guards

## Safe alternatives for read-modify-write patterns

For each read-modify-write (RMW) pattern found, verify whether one of the following safe alternatives is used:

### DB atomic operation
`Model::where('id', $id)->increment('balance', $amount)` — single SQL statement, no application-layer round-trip.

### Pessimistic lock
`Model::where('id', $id)->lockForUpdate()->first()` inside a transaction. Ensures no other process can read the same row until the transaction completes.

### Optimistic lock
Version or timestamp column checked in the `UPDATE WHERE` clause; retry on mismatch. Suitable when contention is low and retries are acceptable.

### Unique constraint / idempotency key
Prevents duplicate processing even if the job runs twice. The database enforces uniqueness regardless of application-level timing.

### Database transaction wrapping the full RMW
Ensures isolation at the correct level. The entire read-modify-write sequence executes atomically.

## `firstOrCreate` / `updateOrCreate` safety

- These methods are NOT atomic in MySQL without a unique index — two concurrent calls can insert duplicates.
- Verify a unique DB index backs every `firstOrCreate` / `updateOrCreate` call.
- If no unique index exists, flag as **Critical**.

## Cache stampede prevention

- Look for cache miss followed by expensive computation followed by cache write sequences.
- If multiple workers can trigger the same computation simultaneously, flag it.
- Recommend atomic cache lock: `Cache::lock($key)->get(fn() => ...)` or equivalent.

## Locking scope and transaction isolation

- Verify locks are held for the minimum required time.
- Ensure `lockForUpdate()` is always inside a `DB::transaction()` — a lock without a transaction is useless.
- Check that the transaction isolation level is appropriate (READ COMMITTED vs SERIALIZABLE).
- Flag nested transactions or missing rollback on exception as **Moderate**.
