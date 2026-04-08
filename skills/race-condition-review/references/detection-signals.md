# Detection Signals

## When to apply this skill

Apply this skill when the changed code contains any of the following signals:

- Read-modify-write sequences (load a value, compute in application layer, save back)
- Shared mutable state accessed by multiple workers, jobs, or requests
- Usage of `firstOrCreate`, `updateOrCreate`, `increment`, `decrement`
- Counter, balance, stock, quota, or seat management logic
- Retry-able or re-dispatched jobs that mutate shared records
- Optimistic/pessimistic locking patterns (or the absence of them)
- Cache write-back or cache invalidation on shared keys
- Bulk operations that read and then write in separate steps

## How to identify shared state

- List all DB records, cache keys, counters, or in-memory structures written by the changed code.
- Note which of these can be accessed by more than one process, worker, or HTTP request simultaneously.
- Pay special attention to records that are both read and written within the same request/job.
