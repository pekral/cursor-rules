# Bottleneck Analysis

## What to evaluate

When analyzing Telescope data for performance issues, check for these concrete patterns:

### Database

- **N+1 queries** — many similar queries executed in a loop; look for repeated SELECT with different IDs
- **Slow SQL** — queries with high duration; check for missing or poor index usage
- **Excessive query count** — too many queries per request (typical threshold: >50 is a warning, >200 is critical)
- **Duplicate queries** — identical queries executed multiple times in the same request

### Cache

- **Repeated cache misses** — same key missed multiple times, indicating missing cache-aside logic
- **No cache strategy** — hot data fetched from DB on every request without caching
- **Cache stampede risk** — many concurrent misses on the same key

### Request lifecycle

- **Excessive synchronous work** — heavy computation or external calls in the request cycle that should be queued
- **Heavy serialization** — large payload sizes in request or response bodies
- **Long middleware chains** — middleware adding unnecessary overhead

### Jobs and events

- **Repeated failing jobs** — same job failing and retrying, chained to the request
- **Synchronous dispatching** — jobs dispatched without queue (sync driver)

### Logging

- **Noisy logging** — excessive log entries causing I/O overhead during request processing

## Evidence requirements

Every bottleneck finding must include:
- The specific data point from Telescope that proves the issue (query text, duration, count, etc.)
- Whether it is a confirmed issue or a hypothesis based on partial data
