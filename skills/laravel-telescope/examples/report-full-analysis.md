# Example: Full Telescope Analysis Report

## Laravel Telescope Analysis Report

### Input
- Telescope URL: `https://app.example.com/telescope/requests/9a3f1c7e-4b2d-4e8a-b5c1-abc123def456`
- Scope / filters: none

### Matched request (UI)
- UUID: `9a3f1c7e-4b2d-4e8a-b5c1-abc123def456`
- Method + URI: `GET /api/orders?status=pending`
- Status: 200
- Duration / memory: 1,240 ms / 48 MB
- Timestamp: 2025-03-15 14:22:33 UTC

### Matched request (DB)
- Table path used: `telescope_entries` WHERE uuid = :uuid
- Key match criteria: UUID exact match, timestamp confirmed, method + URI match
- Query summary: 1 row returned, content JSON contains matching method, URI, and status
- Confidence of match: High

### Findings
1. **N+1 query on OrderItem** — 87 SELECT queries to `order_items` table, each fetching items for a single order. Total query time: 620 ms.
2. **No cache on product lookup** — `products` table queried 87 times with different IDs; no cache hits recorded in cache tab.

### Recommended optimizations
1. Change: Add eager loading `Order::with('items')` in `OrderController@index`
   - Why: Eliminates 86 redundant queries by loading all items in one query
   - Expected impact: ~600 ms latency reduction, ~86 fewer DB queries per request
   - Risk: Low — eager loading is a standard Laravel pattern
   - Verification: Re-run the same request and check Telescope query count drops to ~2

2. Change: Add cache-aside for product lookups with 5-minute TTL
   - Why: Product data changes infrequently; caching avoids repeated DB hits
   - Expected impact: ~200 ms reduction on subsequent requests
   - Risk: Low — stale data window is 5 minutes, acceptable for display purposes
   - Verification: Check Telescope cache tab shows hits after first request

### SQL / index notes
- `order_items.order_id` index exists and is used — no index change needed
- `products.id` primary key lookup is efficient; bottleneck is query count, not query speed

### Limitations
- None — full UI and DB access available
