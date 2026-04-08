# Example: Static Analysis Without DB Access

## MySQL Analysis Report

### Query under review

```php
$orders = Order::where('status', 'pending')
    ->where('created_at', '>', now()->subDays(30))
    ->with('user')
    ->orderByDesc('created_at')
    ->paginate(25);
```

Reconstructed SQL:

```sql
SELECT * FROM orders
WHERE status = 'pending'
  AND created_at > '2024-03-09'
ORDER BY created_at DESC
LIMIT 25 OFFSET 0;
```

### Tables inspected

- `orders` — reviewed from migration `2023_01_15_create_orders_table.php`

### Existing indexes

From migration file:

| Table | Index | Columns |
|---|---|---|
| orders | PRIMARY | id |
| orders | (foreign key) | user_id |

No additional indexes found in migrations.

### EXPLAIN summary

EXPLAIN could not be executed — no database access available.

### Problems found

- Likely full table scan on `orders` if the table is large — no index on `status` or `created_at`
- `SELECT *` used (Eloquent default) — wider row reads than necessary
- Offset pagination will degrade on later pages

### Recommended optimizations

1. Add composite index `(status, created_at)` to support filter and sort
2. Consider cursor pagination (`cursorPaginate()`) if users navigate deep pages
3. Use `->select()` to limit columns if the full model is not needed

### Suggested index changes

```php
Schema::table('orders', function (Blueprint $table) {
    $table->index(['status', 'created_at'], 'idx_status_created');
});
```

### Risks and trade-offs

- Without EXPLAIN, the row count and actual plan are unknown
- If the table is small, the index may not provide measurable benefit

### Confidence / limitations

- Medium confidence — static analysis only, no EXPLAIN or live schema verification performed
- Index suggestion is based on migration files; actual schema may differ
