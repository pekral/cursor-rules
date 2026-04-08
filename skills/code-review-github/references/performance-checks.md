# Performance Checks

## N+1 Queries
- Relationships accessed inside loops without eager loading (`with()`, `load()`).
- Model calls inside `foreach`, `map`, or `each` that trigger individual queries.
- Fix: use `with()` on the initial query or `load()` before the loop.

## Missing Indexes
- Columns used in `WHERE`, `JOIN`, `ORDER BY`, or `GROUP BY` without an index.
- Composite queries where column order in the index does not match query predicates.
- Low-cardinality columns used as the only index — ineffective filtering.

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
- Using `DATE()`, `LOWER()`, or other functions on indexed columns in WHERE — prevents index usage.
- Missing `LIMIT` on queries that could return unbounded results.
- Using `DISTINCT` or `GROUP BY` as a workaround for duplicate joins.

## Caching
- Hot paths (frequently accessed, rarely changing data) without caching.
- Cache keys that do not include all discriminating parameters — cache collisions.
- Missing cache invalidation after writes — stale data served to users.

## Memory
- Loading large datasets into memory with `get()` — use `chunk()` or `cursor()`.
- Accumulating results in arrays without bounds — memory grows with data size.
- Closures capturing large objects in event listeners or queue handlers.
