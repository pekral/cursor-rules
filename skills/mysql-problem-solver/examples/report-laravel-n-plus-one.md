# Example: Laravel N+1 Detection

## MySQL Analysis Report

### Query under review

```php
$users = User::where('active', true)->get();

foreach ($users as $user) {
    echo $user->orders->count();
}
```

### Tables inspected

- `users`
- `orders`

### Existing indexes

| Table | Index | Columns |
|---|---|---|
| orders | PRIMARY | id |
| orders | idx_user_id | user_id |

### EXPLAIN summary

Not applicable — the issue is an N+1 pattern at the application level.

### Problems found

- N+1 query: for each of N users, a separate `SELECT * FROM orders WHERE user_id = ?` is executed
- Total queries: 1 (users) + N (orders per user)

### Recommended optimizations

1. Replace with eager loading and `withCount`:

```php
$users = User::where('active', true)
    ->withCount('orders')
    ->get();

foreach ($users as $user) {
    echo $user->orders_count;
}
```

2. This reduces the query count to 2 (one for users, one aggregated count query)

### Suggested index changes

No index changes needed — `idx_user_id` already supports the join.

### Risks and trade-offs

- None — eager loading is strictly better here

### Confidence / limitations

- High confidence — N+1 pattern is clearly visible in the code
