# Performance Checks

## N+1 Queries
- Relationships accessed inside loops without eager loading (`with()`, `load()`).
- Model calls inside `foreach`, `map`, or `each` that trigger individual queries.
- Fix: use `with()` on the initial query or `load()` before the loop.

## Missing Indexes
- Columns used in `WHERE`, `JOIN`, `ORDER BY`, or `GROUP BY` without an index.
- Composite queries where column order in the index does not match query predicates.

## Unnecessary Loops
- Processing entire collections when a single query or set-based operation suffices.
- Nested loops over large datasets — prefer batch operations or chunking.
- Repeated lookups in loops — cache reference data before the loop.

## Expensive Operations
- `SELECT *` instead of selecting only needed columns.
- Loading all records with `get()` when `chunk()` or `cursor()` would bound memory.
- Synchronous HTTP calls in the request lifecycle — prefer queued jobs.
- Image/file processing during HTTP requests — offload to background jobs.

## Query Optimization
- Functions on indexed columns in WHERE (`DATE()`, `LOWER()`) prevent index usage.
- Missing `LIMIT` on queries that could return unbounded results.

## Caching
- Hot paths without caching.
- Cache keys that do not include all discriminating parameters.
- Missing cache invalidation after writes.

## Memory
- Loading large datasets with `get()` — use `chunk()` or `cursor()`.
- Accumulating results in arrays without bounds.
- Closures capturing large objects in event listeners or queue handlers.
