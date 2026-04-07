---
name: race-condition-review
description: "Use when reviewing code for race conditions, concurrency issues, and shared-state consistency problems. Analyzes read-modify-write patterns, concurrent access to shared resources, and missing atomicity guards. Provides structured findings with severity levels and concrete fixes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/skills/base-constraints.mdc
- Apply @rules/skills/review-only.mdc
- Be realistic and precise — only flag genuine concurrency risks, not hypothetical ones.

**When to apply this skill:**
Apply this skill when the changed code contains any of the following signals:
- Read-modify-write sequences (load a value → compute in PHP → save back)
- Shared mutable state accessed by multiple workers, jobs, or requests
- Usage of `firstOrCreate`, `updateOrCreate`, `increment`, `decrement`
- Counter, balance, stock, quota, or seat management logic
- Retry-able or re-dispatched jobs that mutate shared records
- Optimistic/pessimistic locking patterns (or the absence of them)
- Cache write-back or cache invalidation on shared keys
- Bulk operations that read and then write in separate steps

**Steps:**

### 1. Identify shared state
- List all DB records, cache keys, counters, or in-memory structures written by the changed code.
- Note which of these can be accessed by more than one process, worker, or HTTP request simultaneously.

### 2. Detect read-modify-write (RMW) patterns
- Find every place where code reads a value, computes a result in PHP/application layer, then writes it back.
- Flag sequences like: `$record = Model::find($id); $record->balance -= $amount; $record->save();`
- These are unsafe under concurrency — two processes can read the same value before either writes.

### 3. Check atomicity guards
For each RMW pattern found, verify whether one of the following safe alternatives is used:
- **DB atomic operation**: `Model::where('id', $id)->increment('balance', $amount)` — single SQL, no PHP round-trip.
- **Pessimistic lock**: `Model::where('id', $id)->lockForUpdate()->first()` inside a transaction.
- **Optimistic lock**: version/timestamp column checked in the `UPDATE WHERE` clause; retry on mismatch.
- **Unique constraint / idempotency key**: prevents duplicate processing even if the job runs twice.
- **Database transaction wrapping the full RMW**: ensures isolation at the correct level.

### 4. Inspect job and queue patterns
- Check whether re-dispatched or retried jobs can process the same record multiple times.
- Verify `ShouldBeUnique` or equivalent deduplication is used where appropriate.
- Check `$tries`, `$backoff`, and `$timeout` — retries without idempotency guards amplify race conditions.
- Flag jobs that contain RMW without atomicity guards as **Critical**.

### 5. Check `firstOrCreate` / `updateOrCreate` safety
- These methods are NOT atomic in MySQL without a unique index — two concurrent calls can insert duplicates.
- Verify a unique DB index backs every `firstOrCreate` / `updateOrCreate` call.
- If no unique index exists, flag as **Critical**.

### 6. Check cache stampede risks
- Look for cache miss → expensive computation → cache write sequences.
- If multiple workers can trigger the same computation simultaneously, flag it.
- Recommend atomic cache lock: `Cache::lock($key)->get(fn() => ...)` or equivalent.

### 7. Check locking scope and transaction isolation
- Verify locks are held for the minimum required time.
- Ensure `lockForUpdate()` is always inside a `DB::transaction()` — a lock without a transaction is useless.
- Check that the transaction isolation level is appropriate (READ COMMITTED vs SERIALIZABLE).
- Flag nested transactions or missing rollback on exception as **Moderate**.

### 8. Assess test coverage for concurrency
- Check whether there are tests that send multiple parallel requests or simulate concurrent job execution.
- A passing single-request test does NOT prove the absence of a race condition.
- Recommend: concurrent integration tests using parallel HTTP calls or multiple job dispatches on the same record.
- Suggest using database transactions in tests to verify final state consistency.

**Severity levels:**
- **Critical** — Exploitable race that can cause data corruption, double-spend, duplicate records, or incorrect balances under realistic load.
- **Moderate** — Pattern that is unsafe under concurrent load but requires specific timing or volume to manifest; should be fixed before production scaling.
- **Minor** — Defensive improvement or missing test coverage that reduces confidence without a direct exploit path.

**Report format:**
For each finding:
- **Severity** — Critical / Moderate / Minor
- **Location** — file and line (or method name)
- **Pattern** — the specific anti-pattern detected (e.g. RMW without lock, firstOrCreate without unique index)
- **Risk** — what can go wrong under concurrency
- **Fix** — concrete recommended change with code snippet

**Example findings:**

```
### Critical — Read-modify-write without atomicity guard
Location: app/Services/WalletService.php:42
Pattern: RMW without lock
Risk: Two concurrent requests can both read balance=100, both deduct 50, and both write 50 — resulting in balance=50 instead of 0.
Fix:
// Instead of:
$wallet->balance -= $amount;
$wallet->save();

// Use atomic DB operation:
Wallet::where('id', $wallet->id)->decrement('balance', $amount);
// Or pessimistic lock inside transaction:
DB::transaction(function () use ($wallet, $amount) {
    $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
    $wallet->balance -= $amount;
    $wallet->save();
});
```

**Deliver:** Structured report grouped by severity. End with a one-line summary, e.g. "Summary: 1 Critical, 2 Moderate, 0 Minor". If no concurrency risks are found, state "No race condition risks identified in the reviewed changes."
