---
name: redis-patterns
description: "Use when using Redis in a Laravel app — caching strategies, atomic/distributed locks, rate limiting, stampede protection, pub/sub, pipelines, and key/TTL design beyond raw query tuning."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Redis Patterns

## Constraints
- Apply `@rules/laravel/laravel.mdc` — use the framework's facades (`Cache`, `RateLimiter`, `Redis`), not a raw client.
- Apply `@rules/laravel/queue-debouncing.mdc` for queue/job coalescing concerns when Redis backs the queue.
- Cross-link `@rules/sql/optimalize.mdc` (DB-level caching) — Redis caching sits in front of the query tuning that rule owns; cache the result, do not paper over an unindexed query.
- `final` classes, `declare(strict_types=1)`, Pest tests (use the `array` cache driver in tests unless asserting Redis-specific behavior).
- Always set a TTL. Keys without expiry accumulate and cause memory pressure.

## Use when
- Adding caching, rate limiting, distributed coordination, or pub/sub to a Laravel app.
- Choosing a cache strategy, protecting a cold cache from stampede, or designing key/TTL conventions.
- Configuring Redis as the session, cache, or queue store.

Use Laravel facades throughout. Reach for raw `Redis::command(...)` only for structures the Cache abstraction does not expose (sorted sets, streams).

## Caching Strategies

### Cache-Aside (default for read-heavy data)

```php
$product = Cache::remember("product:{$id}", now()->addMinutes(10), fn () =>
    Product::findOrFail($id),
);
```

`remember()` is read-through cache-aside: returns the cached value or runs the closure, stores it, and returns it. Use `rememberForever()` only with an explicit invalidation path.

### Write-Through (consistency required)

```php
$product->update($data);
Cache::put("product:{$product->id}", $product->fresh(), now()->addMinutes(10));
// Or simply invalidate so the next read repopulates:
Cache::forget("product:{$product->id}");
```

Invalidate (forget) rather than rewrite when the cached shape may differ from the model.

### Cache Tags (grouped invalidation)

```php
Cache::tags(['products', "category:{$categoryId}"])
    ->remember("product:{$id}", now()->addMinutes(10), fn () => Product::findOrFail($id));

Cache::tags(["category:{$categoryId}"])->flush(); // drop the whole group at once
```

Tags require the `redis` (or `memcached`) store — not `file`/`database`. They add key overhead; use for genuine groups, not single keys.

## Stampede Protection

A cold/expired hot key can trigger many concurrent rebuilds (thundering herd). Guard the rebuild with an atomic lock so only one worker recomputes.

```php
public function getReport(string $key): array
{
    return Cache::get($key) ?? Cache::lock("rebuild:{$key}", seconds: 10)->block(5, function () use ($key) {
        // Re-check inside the lock — another worker may have just populated it.
        return Cache::get($key) ?? tap($this->computeReport(), fn ($v) =>
            Cache::put($key, $v, now()->addMinutes(15)),
        );
    });
}
```

- `Cache::lock()` is an atomic `SET NX PX` lock; `block(5, ...)` waits up to 5s to acquire.
- Alternative for very hot keys: stale-while-revalidate — serve the slightly stale value while one worker refreshes in the background, avoiding any blocking.

## Distributed Locks

Coordinate exclusive access across workers/requests.

```php
$lock = Cache::lock('payment:'.$orderId, seconds: 30);
if ($lock->get()) {
    try {
        $this->processPayment($orderId);
    } finally {
        $lock->release(); // always release in finally
    }
}
```

- The TTL must exceed the expected work; on crash the lock auto-expires so it never wedges permanently.
- Only the owner may release — Laravel tracks the owner token, so `release()` is safe. Use `forceRelease()` only deliberately.
- For bounded concurrency (N parallel, not 1), use `Redis::funnel('job')->limit(3)->then(fn () => ...)`.

## Rate Limiting

Prefer the framework limiter over hand-rolled counters.

```php
use Illuminate\Support\Facades\RateLimiter;

$executed = RateLimiter::attempt("send:{$user->id}", maxAttempts: 5, function (): void {
    $this->sendMessage();
}, decaySeconds: 60);

if (! $executed) {
    abort(429); // or RateLimiter::availableIn($key) for retry hint
}
```

- HTTP routes: define a named limiter (`RateLimiter::for('api', fn ($r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()))`) and apply `throttle:api` middleware.
- For high-throughput throttling, `Redis::throttle('key')->allow(60)->every(60)->then($ok, $tooMany)` runs the check in a single atomic Redis call.

## Key Naming & TTL Discipline

```
{app}:{resource}:{id}            myapp:product:123
{app}:{resource}:{id}:{field}    myapp:order:456:status
{app}:{resource}:{date}          myapp:stats:pageviews:2026-06-14
```

| Data | Suggested TTL |
|------|---------------|
| API/query response cache | 5–15 min |
| User session | 24h |
| Rate-limit window | = window size |
| Short-lived token | 5–10 min |
| Reference/static data | 1h–1 week |

- Use Laravel's `cache.prefix` for the app namespace; do not hand-prefix every key.
- Never run `KEYS *` in production (O(N), blocks the server) — use `SCAN` via `Redis::scan()`.

## Eviction Policy

Set `maxmemory` + `maxmemory-policy` in `redis.conf` per role:

| Policy | Use for |
|--------|---------|
| `allkeys-lru` | General cache (evict least-recently-used) |
| `volatile-lru` | Mixed cache + must-keep data (only evict keys with TTL) |
| `allkeys-lfu` | Skewed access (hot keys survive) |
| `noeviction` | Queue / session store — errors instead of dropping data |

Critical: do **not** point a Redis queue/session connection at an `allkeys-lru` instance — it will silently evict jobs/sessions. Separate the cache instance/DB from the queue instance.

## Pub/Sub

Fire-and-forget broadcast; no delivery guarantee or replay.

```php
Redis::publish('orders', json_encode(['id' => $order->id]));

// Long-running listener (artisan command):
Redis::subscribe(['orders'], function (string $message): void {
    $this->handle(json_decode($message, true));
});
```

If you need durability, consumer groups, or replay, use the Laravel queue (below) or Redis Streams via `Redis::command('XADD', ...)` — Pub/Sub drops messages for absent subscribers.

## Pipelines & Transactions

Batch many commands in one round trip; use a transaction when they must apply atomically.

```php
Redis::pipeline(function ($pipe) use ($ids): void {
    foreach ($ids as $id) {
        $pipe->del("product:{$id}");
    }
}); // one round trip, NOT atomic

Redis::transaction(function ($tx) use ($key): void {
    $tx->incr($key);
    $tx->expire($key, 60);
}); // MULTI/EXEC — all-or-nothing
```

Pipelines cut latency for bulk ops; transactions add atomicity. Do not loop single commands when a pipeline fits.

## Queues, Sessions & Stores

- **Queues:** set `QUEUE_CONNECTION=redis`. Run **Laravel Horizon** for Redis queues — it gives supervisor config, metrics, and a dashboard. For coalescing bursty jobs see `@rules/laravel/queue-debouncing.mdc`.
- **Sessions / cache:** `SESSION_DRIVER=redis`, `CACHE_STORE=redis`. Keep sessions/queue on a `noeviction` instance and cache on an `allkeys-lru` instance (or distinct DB indexes) so cache eviction never drops a session or job.
- Size the predis/phpredis pool and set `socket_timeout` / `read_timeout` so a stalled Redis fails fast instead of hanging requests.

## Done when
- Every key has an intentional TTL and follows the naming convention.
- Hot/cold cache rebuilds are stampede-protected with `Cache::lock`.
- Cache and queue/session use separate instances or DBs with matching eviction policies.
- Rate limiting uses `RateLimiter` / `Redis::throttle`, not ad-hoc counters.
- Pest tests cover cache hit/miss and lock paths; no `KEYS *` in production code.
