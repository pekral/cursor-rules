# Example: N+1 Query Detection

## Laravel Telescope Analysis Report

### Input
- Telescope URL: `https://app.example.com/telescope/requests/c4d5e6f7-8a9b-0c1d-2e3f-456789abcdef`
- Scope / filters: tag = `user:42`

### Matched request (UI)
- UUID: `c4d5e6f7-8a9b-0c1d-2e3f-456789abcdef`
- Method + URI: `GET /dashboard`
- Status: 200
- Duration / memory: 2,100 ms / 64 MB
- Timestamp: 2025-03-16 11:30:45 UTC

### Matched request (DB)
- Table path used: `telescope_entries` WHERE uuid = :uuid, joined with `telescope_entries_tags`
- Key match criteria: UUID exact match, tag `user:42` confirmed in tags table
- Query summary: 1 request entry + 312 child query entries with same family_hash
- Confidence of match: High

### Findings
1. **N+1 on User->posts relationship** — 1 query to load user, then 150 individual queries to `posts` table (one per post). Total: 840 ms.
2. **N+1 on Post->comments count** — 150 queries to count comments per post. Total: 620 ms.
3. **Duplicate query for settings** — `SELECT * FROM settings WHERE key = 'dashboard_config'` executed 3 times identically. Total: 15 ms.

### Recommended optimizations
1. Change: Use `User::with('posts.commentsCount')` eager loading
   - Why: Collapses 301 queries into 3 queries (user, posts, comment counts)
   - Expected impact: ~1,400 ms latency reduction
   - Risk: Low — standard eager loading
   - Verification: Query count in Telescope drops from 312 to ~12

2. Change: Cache `dashboard_config` setting in a singleton or request-scoped cache
   - Why: Same query repeated 3 times per request; value does not change within a request
   - Expected impact: ~10 ms (minor, but eliminates unnecessary DB calls)
   - Risk: Low
   - Verification: Telescope shows 1 query instead of 3 for settings

### SQL / index notes
- `posts.user_id` index is present and used
- Consider adding `comments.post_id` index if not present (verify with EXPLAIN)

### Limitations
- None
