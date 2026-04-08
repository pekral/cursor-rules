# Example: Performance Improvement Proposal

## Proposal

Add a response caching layer for the most-hit read-only API endpoints using HTTP cache headers and an in-memory store, reducing p95 latency by an estimated 60%.

## Expected Benefits

- **Business:** Faster page loads improve user retention and reduce infrastructure cost
- **Technical:** Offloads repeated computation from the application layer; reduces database query volume

## Evaluation

| Dimension | Rating |
|---|---|
| Impact | High |
| Complexity | Medium |
| Risk | Medium |
| Reversibility | High |

## Key Risks and Mitigations

| Risk | Mitigation |
|---|---|
| Stale data served to users | Set short TTL (30s); add cache-bust endpoint for admin use |
| Cache invalidation bugs | Start with read-only, idempotent endpoints only; no caching for authenticated or personalized responses |
| Memory pressure on application server | Cap cache size; monitor memory usage with alerts |

## Minimal Implementation Plan

1. Identify the top 3 endpoints by request volume using existing access logs
2. Add a caching middleware that stores responses keyed by URL and query params
3. Set `Cache-Control` and `ETag` headers on cached responses
4. Add a `/cache/purge` admin endpoint for manual invalidation

## Test Strategy

- Hit a cached endpoint twice; verify second response is served from cache (check response headers)
- Mutate underlying data and verify cache expires after TTL
- Load test cached vs uncached endpoints and compare p95 latency

## Rollout / Rollback

- **Rollout:** Enable caching for one endpoint at a time; monitor hit rate and latency
- **Rollback:** Remove caching middleware; no data migration needed
