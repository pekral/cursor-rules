# Concurrency Test Coverage

## Why single-request tests are insufficient

A passing single-request test does NOT prove the absence of a race condition. Race conditions only manifest when multiple processes access shared state simultaneously.

## What to look for in existing tests

- Tests that send multiple parallel requests to the same endpoint
- Tests that simulate concurrent job execution on the same record
- Tests that verify final state consistency after concurrent operations
- Use of database transactions in tests to verify isolation

## Recommended test strategies

### Concurrent HTTP requests
Send N parallel requests to the same endpoint with the same or overlapping data. Verify the final state matches the expected result (e.g., balance decremented exactly N times).

### Concurrent job execution
Dispatch the same job N times simultaneously on the same record. Verify no duplicate processing occurred and the final state is consistent.

### Database constraint verification
Attempt to insert duplicate records concurrently and verify the unique constraint prevents duplicates.

### Lock contention testing
Simulate concurrent access with pessimistic locks and verify that processes queue correctly rather than corrupting data.
