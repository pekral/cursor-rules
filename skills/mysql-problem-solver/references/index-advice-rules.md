# Index Advice Rules

When suggesting indexes, follow these rules:

- Do not propose indexes blindly
- Avoid duplicate or near-duplicate indexes without justification
- Prefer composite indexes that match real filter and sort patterns
- Mention leftmost prefix implications
- Warn about insert/update overhead
- Mention when a proposed index helps reads but hurts writes
- Avoid recommending every filtered column as a standalone index

## Composite index ordering

A composite index should follow this general column order:

1. Equality filters first (`WHERE status = 'active'`)
2. Range filters next (`WHERE created_at > '2024-01-01'`)
3. Sort columns last (`ORDER BY name`)

## When NOT to add an index

- Table is very small (< 1000 rows) — full scan may be faster
- Column has very low cardinality and no composite opportunity
- Write-heavy table where the read pattern is infrequent
- An existing composite index already covers the query via leftmost prefix
