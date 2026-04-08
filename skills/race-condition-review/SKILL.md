---
name: race-condition-review
description: "Use when reviewing code for race conditions, concurrency issues, and shared-state consistency problems. Analyzes read-modify-write patterns, concurrent access to shared resources, and missing atomicity guards. Provides structured findings with severity levels and concrete fixes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/review-only.mdc
- Be realistic and precise — only flag genuine concurrency risks, not hypothetical ones.

**Scripts:** Use the pre-built scripts in `@skills/race-condition-review/scripts/` to scan for patterns. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/find-rwm-patterns.sh [dir]` | Scan for read-modify-write patterns, locks, cache ops, and transactions |
| `scripts/check-unique-indexes.sh [dir]` | Cross-reference firstOrCreate/updateOrCreate calls with unique index definitions |

**References:**
- `references/detection-signals.md` — when to apply this skill and how to identify shared state
- `references/atomicity-guards.md` — safe alternatives for RMW patterns, firstOrCreate safety, cache stampede, locking scope
- `references/severity-levels.md` — Critical / Moderate / Minor definitions, classification rules, job pattern severity
- `references/concurrency-test-coverage.md` — why single-request tests are insufficient, recommended test strategies

**Examples:** See `examples/` for expected output format:
- `examples/report-critical-rwm.md` — critical race condition with fix
- `examples/report-no-issues.md` — clean review with no findings
- `examples/report-moderate-findings.md` — moderate and minor findings

**Steps:**

### 1. Identify shared state
- List all DB records, cache keys, counters, or in-memory structures written by the changed code.
- Note which of these can be accessed by more than one process, worker, or HTTP request simultaneously.
- Use `scripts/find-rwm-patterns.sh` to scan the affected directories.

### 2. Detect read-modify-write (RMW) patterns
- Find every place where code reads a value, computes a result in the application layer, then writes it back.
- Flag sequences like: `$record = Model::find($id); $record->balance -= $amount; $record->save();`
- These are unsafe under concurrency — two processes can read the same value before either writes.

### 3. Check atomicity guards
For each RMW pattern found, verify whether a safe alternative is used per `references/atomicity-guards.md`.

### 4. Inspect job and queue patterns
- Check whether re-dispatched or retried jobs can process the same record multiple times.
- Verify `ShouldBeUnique` or equivalent deduplication is used where appropriate.
- Check `$tries`, `$backoff`, and `$timeout` — retries without idempotency guards amplify race conditions.
- Classify severity per `references/severity-levels.md`.

### 5. Check `firstOrCreate` / `updateOrCreate` safety
- Use `scripts/check-unique-indexes.sh` to cross-reference calls with unique index definitions.
- Verify a unique DB index backs every `firstOrCreate` / `updateOrCreate` call per `references/atomicity-guards.md`.
- If no unique index exists, flag as **Critical**.

### 6. Check cache stampede risks
- Look for cache miss followed by expensive computation followed by cache write sequences.
- Evaluate per `references/atomicity-guards.md` cache stampede section.

### 7. Check locking scope and transaction isolation
- Verify locks and transactions per `references/atomicity-guards.md` locking scope section.
- Flag nested transactions or missing rollback on exception as **Moderate**.

### 8. Assess test coverage for concurrency
- Evaluate per `references/concurrency-test-coverage.md`.
- A passing single-request test does NOT prove the absence of a race condition.
- Recommend concurrent integration tests using parallel HTTP calls or multiple job dispatches on the same record.

**Output contract:** For each finding, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Severity | Yes | Critical / Moderate / Minor per `references/severity-levels.md` |
| Location | Yes | File and line (or method name) |
| Pattern | Yes | The specific anti-pattern detected (e.g., RMW without lock, firstOrCreate without unique index) |
| Risk | Yes | What can go wrong under concurrency |
| Fix | Yes | Concrete recommended change with code snippet |
| Confidence notes | If applicable | Caveats or assumptions (e.g., could not verify index existence, depends on isolation level) |

**Deliver:** Structured report grouped by severity. End with a one-line summary, e.g. "Summary: 1 Critical, 2 Moderate, 0 Minor". If no concurrency risks are found, state "No race condition risks identified in the reviewed changes."
