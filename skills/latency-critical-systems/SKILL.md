---
name: latency-critical-systems
description: "Use when working on latency-sensitive Laravel paths — realtime dashboards, streaming, queues, caches, or execution gateways — where p95 latency and data freshness matter."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Latency Critical Systems (Laravel)

Engineering approach for latency-sensitive Laravel paths: realtime dashboards,
streaming, ingest workers, queues, caches, and execution gateways where p95
latency and freshness matter. This skill is engineering-focused; it does not
authorize live trading or financial advice.

## Constraints
- Apply `@rules/sql/optimalize.mdc` for every query on the hot path (N+1, eager loading, index usage, batching)
- Apply `@rules/laravel/laravel.mdc` for framework-level structure and caching choices
- Apply `@rules/laravel/queue-debouncing.mdc` when smoothing bursty queue work
- Measure, do not guess — every claim about latency must come from a real readback.
- Never trade correctness for speed (see Guardrails).

## Use when
- A page, API route, broadcast, or dashboard must hit a latency target (p95/p99).
- Queue lag, cache staleness, or freshness age is a visible problem.
- Streaming / websocket freshness or execution-gateway timing is in scope.
- You are deciding where to cache, batch, replicate, or move compute.

## 1. Split the metrics

Do not collapse everything into "fast." Track separately:

- p50, p95, p99 latency (one slow tail dominates user perception);
- throughput (requests/jobs per second);
- freshness age (how old the displayed data is);
- queue depth and queue wait time;
- cache hit rate;
- provider/external API response time;
- browser render time;
- correctness under load;
- failure and retry behavior.

Capture them with real tools: response timing headers, `Horizon` metrics,
`Redis` `INFO`/`MONITOR`, slow-query log, and Laravel Telescope for per-request
timing breakdowns.

## 2. Map the hot path

Write the path from event to visible state, then measure each segment:

```text
source event -> provider API -> ingest job -> queue (Redis/Horizon) -> cache (Redis)
-> Octane worker / route -> broadcast (websocket) -> Livewire/browser render -> user
```

For each segment record where time goes. The bottleneck is usually one segment,
not the whole chain — instrument before optimizing.

## 3. Optimization order

Apply in this order; stop when the target is met.

1. **Remove unnecessary round trips.** Collapse repeated queries; resolve N+1
   with eager loading (`with(...)`, `withCount(...)`) per `@rules/sql/optimalize.mdc`.
   N+1 on a hot path is the single most common latency killer.
2. **Cache stable reads with freshness metadata.** Use `Cache::remember()` /
   `Cache::flexible()` against Redis for reads that tolerate a known staleness
   window. Store the computed-at timestamp alongside the value.

   ```php
   $stats = Cache::remember('dashboard:stats', now()->addSeconds(30), fn () =>
       Order::query()->selectRaw('count(*) c, sum(total) t')->first()
   );
   ```

3. **Batch small calls and writes.** Combine per-row queries into bulk
   operations (`whereIn`, `upsert`, single keyed read) rather than looping. For
   bursty event streams, debounce/coalesce queued work per
   `@rules/laravel/queue-debouncing.mdc` so one job processes a window of events.
4. **Move compute closer to the data or user.** Push aggregation into SQL
   (`@rules/sql/optimalize.mdc`) instead of hydrating models in PHP; serve reads
   from a DB read replica where the connection supports it.
5. **Split hot and cold paths.** Keep the request fast: do the minimum
   synchronously, dispatch the rest to a queue. Run hot routes under **Laravel
   Octane** so the framework stays booted between requests.
6. **Apply backpressure before queues grow unbounded.** Cap concurrency with
   Horizon `maxProcesses` / rate limiting; shed or defer load when queue depth
   crosses a threshold rather than letting lag compound.
7. **Stream only when it improves freshness.** Use broadcasting / websockets
   (Reverb / Echo) to push fresh data instead of client polling — but only where
   freshness genuinely improves the experience.
8. **Add canaries** for stale cache, degraded providers, and growing queue depth.

## 4. Verification — real readbacks

Never claim a latency win from a label; read it back from the running system:

- **HTTP timing & headers** — measure the route; add a `Server-Timing` header or
  read response time from logs / Telescope.
- **Cache state** — confirm hit rate via Redis (`INFO stats`) and verify the
  freshness timestamp stored with cached values.
- **Queue state** — check Horizon for wait time, depth, and failed jobs; confirm
  the hot path is not blocked behind a slow queue.
- **Query plans** — run `EXPLAIN` on the hot query (see `@rules/sql/optimalize.mdc`)
  to confirm index usage after a rewrite.
- **Freshness** — read the provider/source timestamp and the displayed value;
  confirm the gap is within the agreed staleness window.
- **Browser** — verify actual UI freshness (Livewire poll/broadcast updates), not
  just server numbers.

For execution-adjacent paths, also verify source-data age, provider status, and
kill-switch / degraded-mode behavior before calling the path ready.

## Guardrails

- Do not optimize latency by dropping required validation or authorization.
- Do not hide stale data behind fast cache hits — surface freshness age; never
  let a fast cache hit masquerade as live data.
- Do not claim millisecond behavior from client labels without measurement.
- Do not gate risky changes loosely: execution-impacting, destructive-migration,
  or customer-facing deploys need an explicit approval gate and a rollback path.
- Keep secrets and private payloads out of logs and benchmark artifacts.

## Done when
- Each tracked metric (p50/p95/p99, throughput, freshness, queue depth, cache
  hit rate) has a real, measured value — before and after.
- The hot path is mapped and the actual bottleneck segment is identified.
- Optimizations were applied in order and each win is confirmed by a readback
  (Telescope/Horizon/Redis/EXPLAIN/browser), not assumption.
- Validation, authorization, and freshness honesty are intact; risky deploys are
  gated.
