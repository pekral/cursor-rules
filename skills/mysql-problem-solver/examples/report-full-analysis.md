# Example: Full Analysis with DB Access

## MySQL Analysis Report

### Query under review

```sql
SELECT o.id, o.total, u.name, u.email
FROM orders o
JOIN users u ON u.id = o.user_id
WHERE o.status = 'pending'
  AND o.created_at > '2024-01-01'
ORDER BY o.created_at DESC
LIMIT 50;
```

### Tables inspected

- `orders` (1.2M rows)
- `users` (85K rows)

### Existing indexes

| Table | Index | Columns |
|---|---|---|
| orders | PRIMARY | id |
| orders | idx_user_id | user_id |
| users | PRIMARY | id |

### EXPLAIN summary

| table | type | key | rows | Extra |
|---|---|---|---|---|
| orders | ALL | NULL | 1,200,000 | Using where; Using filesort |
| users | eq_ref | PRIMARY | 1 | |

### Problems found

- Full table scan on `orders` — no index covers `status` + `created_at` filter
- Filesort triggered due to missing index on `created_at` for the sort

### Recommended optimizations

1. Add composite index on `orders(status, created_at)` to cover both the filter and the sort
2. The existing `idx_user_id` is still needed for other queries; do not remove

### Suggested index changes

```sql
ALTER TABLE orders ADD INDEX idx_status_created (status, created_at);
```

Laravel migration:

```php
Schema::table('orders', function (Blueprint $table) {
    $table->index(['status', 'created_at'], 'idx_status_created');
});
```

### Risks and trade-offs

- The new index adds minor write overhead on `INSERT` and `UPDATE` to `orders`
- For this query pattern the improvement is significant (table scan eliminated)

### Confidence / limitations

- High confidence — EXPLAIN was executed against the live database
- Index suggestion validated against current schema
