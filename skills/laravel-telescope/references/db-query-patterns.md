# DB Query Patterns

## Telescope tables

Standard Telescope storage uses these tables:

| Table | Purpose |
|---|---|
| `telescope_entries` | All recorded entries (requests, queries, jobs, etc.) |
| `telescope_entries_tags` | Tags associated with entries |
| `telescope_monitoring` | Monitored tags (if tag monitoring is enabled) |

## Safe query patterns

### Fetch a single entry by UUID

```sql
SELECT uuid, type, family_hash, content, created_at
FROM telescope_entries
WHERE uuid = :uuid
LIMIT 1;
```

### Fetch entries by family hash

```sql
SELECT te.uuid, te.type, te.created_at, tet.tag
FROM telescope_entries te
LEFT JOIN telescope_entries_tags tet ON tet.entry_uuid = te.uuid
WHERE te.family_hash = :family_hash
ORDER BY te.created_at DESC
LIMIT 200;
```

### Fetch recent requests in a time window

```sql
SELECT uuid, type, content, created_at
FROM telescope_entries
WHERE type = 'request'
  AND created_at BETWEEN :from AND :to
ORDER BY created_at DESC
LIMIT 100;
```

## Safety rules

- **Always use bound parameters** — never concatenate raw user input into SQL.
- **Avoid broad unbounded scans** — Telescope tables can be very large; always use LIMIT and WHERE clauses.
- **Select only required columns** — the `content` column contains large JSON blobs; omit it when only metadata is needed.
- **Prefer indexed lookups** — `uuid`, `type`, `family_hash`, and `created_at` are typically indexed.
- **Never run destructive operations** (DELETE, TRUNCATE, UPDATE) against Telescope tables unless explicitly instructed and justified.
