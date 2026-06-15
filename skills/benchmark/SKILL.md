---
name: benchmark
description: "Use when measuring performance baselines or detecting regressions before and after a change in a Laravel app — page Core Web Vitals, API latency percentiles, build/test velocity, and DB query timing, stored as git-tracked baselines for team comparison."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Benchmark (Laravel)

Measure real performance baselines for a Laravel app and detect regressions by
comparing before/after a change. This skill **measures** — it produces honest
numbers and verdicts. For design guidance on hot paths, defer to
`@skills/latency-critical-systems/SKILL.md`; to act on a regression with an
optimization loop, hand off to `@skills/benchmark-optimization-loop/SKILL.md`.

## Constraints
- Apply `@rules/sql/optimalize.mdc` when reading or interpreting query timing (N+1, index usage, SARGable filters).
- Apply `@rules/code-testing/general.mdc` if you add or touch any benchmark test (Pest, no `describe()`).
- Measure, never guess — every number must be a real readback from a running system, command, or tool.
- Control for noise: separate warm vs cold, run repeats (≥5), report median plus p95, fix one variable at a time.
- Same machine, same dataset, same config for before and after — note the environment so a teammate can reproduce.
- Keep secrets and private payloads out of baseline JSON and logs.

## Use when
- You need a performance baseline for a page, API route, build step, or query.
- A change (PR, dependency bump, query rewrite, config change) may have caused a regression and you must prove it either way.
- You are comparing two implementations or stack options on the same workload.
- Someone claims "it's faster/slower" without a measured before/after.

Do not use for live design advice (use `latency-critical-systems`) or for
diagnosing one Telescope request (use `@skills/laravel-telescope/SKILL.md`).

## Execution

Pick the modes that match the change. Always record environment first:
app URL, commit SHA, `APP_ENV`, dataset size, cache/opcache state, and whether
the run is **cold** (first hit, caches cleared) or **warm** (steady state).

### Mode A — Page performance (Core Web Vitals)
Measure against the running Laravel app (`php artisan serve` or the real host).
- Run Lighthouse: `npx lighthouse <url> --output=json --output-path=./lh.json --only-categories=performance --chrome-flags="--headless"`.
- Capture: **LCP** (target < 2.5s), **CLS** (< 0.1), **INP** (< 200ms), **FCP** (< 1.8s), **TTFB** (< 0.8s).
- Capture resource weight: total transfer, JS/CSS bytes (post-Vite build), render-blocking resources, request count.
- Run 3–5 times; report median. Lighthouse is noisy — discard the first (cold) run unless cold is the metric you want.

### Mode B — API performance (latency percentiles + load)
Target real Laravel routes/controllers. Authenticate as needed; use a realistic payload.
- Quick percentiles: `wrk -t4 -c50 -d30s --latency https://app.test/api/orders` (reports p50/p90/p99).
- Apache Bench alternative: `ab -n 1000 -c 50 https://app.test/api/orders`.
- Scripted scenarios (auth, POST bodies, ramp): use `k6` (`k6 run script.js`).
- Capture: **p50 / p95 / p99** latency, throughput (req/s), error rate, response size. Warm the route first, then measure.

### Mode C — Build / dev velocity
- `composer install` (cold = no vendor, no cache): time it.
- Asset build: `time npm run build` (Vite production) and HMR warm-reload feel.
- Test suite: `time php artisan test` or `vendor/bin/pest` (note parallel vs serial).
- Static analysis: `time vendor/bin/phpstan analyse` and `time vendor/bin/pint --test`.
- Run each at least twice; report cold and warm separately (Composer/PHPStan caches matter).

### Mode D — DB query timing
- Identify the hot query via `@skills/laravel-telescope/SKILL.md` or the slow-query log.
- Run `EXPLAIN` (or `EXPLAIN ANALYZE` on MySQL 8) per `@rules/sql/optimalize.mdc`; record rows examined, key used, and whether a full scan occurs.
- Time the query itself, not the surrounding PHP, to isolate DB cost; cross-check with the Telescope duration for the same request.

### Before / After comparison
1. On the base state (before the change), run the relevant modes and write the baseline JSON.
2. Apply the change. Re-run the **same** modes on the **same** environment and dataset.
3. Compute the delta and verdict per metric. Re-run any metric whose delta is within run-to-run noise before declaring a regression or win.

## Output

### Baseline JSON
Store git-tracked, one file per logical target, under `tests/Benchmark/` (or
`.benchmarks/` if you prefer a non-test location). Commit so the team shares one
reference.

```json
{
  "target": "GET /api/orders",
  "mode": "api",
  "captured_at": "2026-06-15T10:00:00Z",
  "commit": "abc1234",
  "environment": { "app_env": "local", "dataset": "10k orders", "state": "warm", "runs": 5, "tool": "wrk" },
  "metrics": {
    "p50_ms": 42,
    "p95_ms": 110,
    "p99_ms": 180,
    "throughput_rps": 940,
    "error_rate": 0.0
  }
}
```

For page mode use keys `lcp_ms`, `cls`, `inp_ms`, `fcp_ms`, `ttfb_ms`,
`js_bytes`, `transfer_bytes`; for build mode `seconds` per step (cold/warm);
for DB mode `query_ms`, `rows_examined`, `key_used`.

### Comparison table
Report one row per metric with the delta and an explicit verdict.

| Metric | Before | After | Delta | Verdict |
| --- | --- | --- | --- | --- |
| p95 (ms) | 110 | 78 | -29% | improved |
| p99 (ms) | 180 | 175 | -3% | noise |
| throughput (rps) | 940 | 1280 | +36% | improved |
| error rate | 0.0% | 0.4% | +0.4pp | regressed |

Verdict legend: `improved` / `regressed` / `noise` (delta within run-to-run
variance) / `target-miss` (still below the target threshold). State the
threshold and the noise band you used.

## Done when
- Environment is recorded (URL, commit, env, dataset, cold/warm, run count, tool) so the run is reproducible.
- Each in-scope metric has a real measured value from at least the agreed number of repeat runs — never an estimate.
- A git-tracked baseline JSON exists for every measured target.
- For a change review, a before/after comparison table with per-metric delta and verdict is produced, and noise-band deltas are not reported as wins or regressions.
- Any confirmed regression or target-miss is flagged for `@skills/benchmark-optimization-loop/SKILL.md` or `@skills/mysql-problem-solver/SKILL.md` as appropriate.
