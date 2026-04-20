# SQL Patterns for Telescope Tables

Suggested SQL patterns for querying Telescope storage (adapt per storage driver).

## Fetch entry by UUID

```sql
SELECT uuid, type, family_hash, content, created_at
FROM telescope_entries
WHERE uuid = :uuid
LIMIT 1;
```

## Fetch entries by family hash

```sql
SELECT te.uuid, te.type, te.created_at, tet.tag
FROM telescope_entries te
LEFT JOIN telescope_entries_tags tet ON tet.entry_uuid = te.uuid
WHERE te.family_hash = :family_hash
ORDER BY te.created_at DESC
LIMIT 200;
```

## Fetch requests by time range

```sql
SELECT uuid, type, content, created_at
FROM telescope_entries
WHERE type = 'request'
  AND created_at BETWEEN :from AND :to
ORDER BY created_at DESC
LIMIT 100;
```

## Notes

- Use bound parameters; never concatenate raw user input.
- Avoid broad unbounded scans on large Telescope tables.
- If JSON fields are large, select only required columns.
