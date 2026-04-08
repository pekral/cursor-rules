# Laravel Architectural Patterns

## Layer responsibilities

- **Controllers:** Slim; delegate to Services; accept FormRequest only; never `validate()` in controller.
- **Services:** Hold business logic; return DTOs or models.
- **Repositories:** Read-only.
- **ModelManagers:** Write-only.
- **Jobs, Events, Commands:** Slim; delegate to Services.
- New controller actions must have corresponding Request classes.

## SOLID and code quality

- Ensure SRP in each class and apply SOLID principles so that the code is readable for developers.
- Unnecessary complexity; large functions; repeated logic; oversized classes; mixed responsibilities.
- Recommend: simplify structure, improve cohesion, split large units.
- Single responsibility; DTOs not `array<mixed>`; DRY; clear interfaces; no magic numbers (use constants).
- Naming: purpose-revealing; PascalCase/camelCase/kebab-case per type.
- Explicitly detect and report **DRY violations** (duplicated logic, duplicated validation rules, repeated branching/condition blocks, and copy-pasted code paths) as findings with actionable refactoring recommendations.

## Large data processing

- Prefer `chunk()` or `cursor()` over `get()` for large result sets. `get()` loads everything into memory and does not scale.
- **chunk(size):** Use when memory must stay bounded and you do bulk updates or batch work. Tune size (e.g. 200-500) to balance memory vs round-trips.
- **cursor():** Use for read-only iteration over very large datasets (e.g. exports); single row at a time, generator-based, safe under concurrent writes.
- Do not process large collections in a single request: offload to jobs/queues, process in batches, consider rate limiting or backpressure.
- Inside chunks/cursors: check for N+1; eager-load relations used in the loop. Prefer set-based updates over row-by-row in PHP.
- Avoid nested loops over large data; prefer chunk/cursor and set-based or batched work; cache repeated lookups (e.g. config, reference data).

## N+1 queries

- Relationships used in loops must be eager-loaded (`with()`, `load()`); no DB or model calls inside loops that could be batched.

## Performance

- Long or heavy work: run in queues/jobs, not in the request; avoid blocking I/O in the hot path.
- Memory: unresolved references, uncleared timers/listeners/closures; for large datasets ensure chunk/cursor (not `get()`) and bounded batch size.
- Scalability: locking, queue depth, missing caching for hot paths, data structures or algorithms that do not scale with volume.

## Type safety and defensive programming

- Check for proper error handling robustness, type safety, and defensive programming patterns.
- Verify guard clauses, null checks, and safe return types.

## Testing with Laravel

- Laravel: prefer `Http::fake()` over Mockery.
