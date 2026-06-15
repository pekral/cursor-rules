---
name: benchmark-optimization-loop
description: "Use when a goal is vague speed (\"make it faster\", \"reduce p95\", \"cut query time\") and you need a bounded, measured loop that promotes only verified, correctness-preserving wins instead of guessed micro-tweaks."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Benchmark Optimization Loop (Laravel/PHP)

Turn "make it faster" into a recursive, measured loop. Every promoted change
beats a recorded baseline on a real measurement and keeps correctness green.
No baseline, no budget, no promotion.

## Constraints
- Apply `@rules/sql/optimalize.mdc` for any query change (N+1, eager loading, index usage, batching).
- Apply `@rules/code-testing/general.mdc` — the correctness gate is the project test suite.
- Measure, never guess. Every win is a real readback, not a label or intuition.
- Never trade correctness for speed: a faster variant that changes output is rejected, not promoted.
- Defer all measurement mechanics (how to run, warm-up, repetitions, noise) to `@skills/benchmark/SKILL.md`. This skill owns the loop and the gate, not the stopwatch.

## Use when
- The goal is unquantified speed: "make it faster", "it's slow", "reduce p95".
- You are eliminating an N+1, tuning a query, choosing a cache strategy, or raising queue throughput.
- Multiple candidate fixes exist and you must pick the one that actually wins.
- A "faster" claim was made without a before/after number.

Do not use for first-time correctness (use `@skills/test-driven-development/SKILL.md`)
or pure latency architecture without a comparison loop (`@skills/latency-critical-systems/SKILL.md`).

## Required baseline

Before touching any code, write down all five. If any is missing, stop and define it.

1. **Operation** — the exact thing optimized (route, Action, job, query).
2. **Correctness gate** — the tests that must stay green (per `@rules/code-testing/general.mdc`). Identical output for identical input.
3. **Metric** — pick one primary: wall time, p95 latency, rows/sec, queries/request, cost/run, memory, queue throughput.
4. **Baseline value** — measure the current code via `@skills/benchmark/SKILL.md`. Record the number and its variance.
5. **Budget** — the stopping line, set up front:
   - target (e.g. "p95 < 200 ms" or "≤ 5 queries/request"), AND
   - cost cap (time/effort/iterations you will spend), AND
   - noise threshold (smallest delta that counts as real, from the baseline variance).

A baseline without variance is not a baseline — you cannot tell a win from noise.

## Loop

Run per iteration. The **current winner** starts as the baseline.

1. **Measure** — get a fresh number for the current winner via `@skills/benchmark/SKILL.md` (identical inputs, warm-up, repeated runs).
2. **Identify bottleneck** — from data, not hunch. Use `@skills/laravel-telescope/SKILL.md` for per-request query/timing breakdown, `EXPLAIN` (`@rules/sql/optimalize.mdc`) for query plans, `@skills/mysql-problem-solver/SKILL.md` for schema/index issues, Horizon for queue lag. The bottleneck is usually one segment.
3. **One hypothesis → one variant** — change exactly one thing. State the hypothesis ("eager-load `with('items')` removes the N+1"). One variable per variant or you cannot attribute the delta.
   - Laravel examples: eager-load to kill an N+1; add a covering index; replace per-row loop with `whereIn`/`upsert`; `Cache::remember()` a stable read; raise `maxProcesses`/chunk size for queue throughput.
4. **Test with identical inputs** — same dataset, same warm-up, same repetition count as the baseline. Different inputs invalidate the comparison.
5. **Reject or promote vs the current winner**:
   - **Reject** if the correctness gate fails, the delta is within the noise threshold, or it regresses any tracked metric.
   - **Promote** to new current winner only if it beats the current winner by more than the noise threshold AND keeps every gate green.
6. **Codify** — fold the promoted variant into the code; commit with the exact command and before/after numbers in the message.
7. **Confirm** — re-measure the promoted code from a clean state to prove the win is reproducible, not a fluke.

Always compare against the **current accepted winner**, not merely the previous run — a lucky run must not become the bar.

## Variant table

Keep a running ledger. Never discard rejected rows; they prevent re-testing dead ends.

| # | Hypothesis | Change | Metric (primary) | vs winner | Correctness | Decision | Note |
|---|------------|--------|------------------|-----------|-------------|----------|------|
| 0 | baseline | none | 412 ms p95 | — | green | winner | variance ±18 ms |
| 1 | N+1 on items | `with('items')` | 96 ms p95 | -316 ms | green | promote | 51→2 queries |
| 2 | cache list 30 s | `Cache::remember` | 88 ms p95 | -8 ms | green | reject | within noise; adds staleness |
| 3 | covering index | add `(user_id,created_at)` | 71 ms p95 | -25 ms | green | promote | EXPLAIN: ref, no filesort |

## Promotion gate

A variant becomes the new default only when **all** hold:

- **Correctness preserved** — full gate green; identical output for identical input (`@rules/code-testing/general.mdc`).
- **Real win** — beats the current winner beyond the noise threshold, confirmed by a clean re-measure.
- **Reproducible** — same result on a repeat run, not a single lucky sample.
- **Rollback path** — isolated in a commit/branch that can be reverted without collateral; risky changes (destructive migration, customer-facing) need an explicit approval gate.
- **Documented** — committed with the exact benchmark command, dataset, and before/after numbers.

## Stop the recursion when any holds

- The **budget target** is met (good enough — do not gold-plate).
- The **cost cap** (iterations/time/effort) is reached.
- The last few variants show **diminishing returns** — deltas shrinking toward the noise threshold.
- The bottleneck has moved to something **out of scope** (provider API, network, hardware).

When you stop, the current winner is the result. Record why you stopped.

## Done when
- Baseline (operation, gate, metric, value+variance, budget) was recorded before any change.
- Each variant tested one hypothesis with identical inputs and is logged in the variant table.
- Every promotion passed the full promotion gate; rejected variants stayed logged.
- The final winner's improvement is confirmed by a clean re-measurement, not a label.
- The correctness gate is green and a rollback path exists for every promoted change.
- A stopping criterion was hit and the reason is recorded.
