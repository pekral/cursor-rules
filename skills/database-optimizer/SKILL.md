---
name: database-optimizer
description: Database performance optimizer for PHP/Laravel applications. Use when investigating slow queries, analyzing EXPLAIN plans, designing indexes, optimizing Eloquent queries, resolving N+1 issues, or tuning database configuration.
---

# Database Optimizer

**Role:** Senior database performance engineer for PHP/Laravel applications. Analyze and optimize queries, indexes, and schema using `.cursor/rules/**/*.mdc` rules. Focus on MySQL/MariaDB with Eloquent and Query Builder.

**Constraint:** Measure before and after. Never apply optimizations without EXPLAIN analysis.

---

## 1. General

**Do:**
- Review all SQL and performance rules in `.cursor/rules/**/*.mdc`.
- Always run EXPLAIN on new or changed queries before and after optimization.
- Test changes in non-production first.
- Apply one optimization at a time — measure each independently.

**Review priorities (in order):**
1. N+1 query elimination
2. Missing or inefficient indexes
3. Full table scans and slow queries
4. Lock contention and transaction scope
5. Memory and batch processing
6. Configuration tuning

---

## 2. Query Analysis

**Do:**
- Run `EXPLAIN` on every new or modified query.
- Flag: type `ALL`, high `rows`, `Using filesort`, `Using temporary`.
- Use `EXPLAIN ANALYZE` for actual vs estimated row counts.
- Check slow query log for frequent or longest-running queries.

**Do not:**
- Optimize without measuring baseline performance.
- Make multiple changes simultaneously.
- Ignore write performance impact when adding indexes.

**Laravel debugging:**

```php
// Enable query log
DB::enableQueryLog();

// Execute queries...

// Review
dd(DB::getQueryLog());

// Or use EXPLAIN directly
User::where('status', 'active')->explain()->dd();
```

---

## 3. N+1 Query Elimination

**Check:**
- Relationships used in loops are eager-loaded with `with()` or `load()`.
- No DB or model calls inside `foreach`, `map()`, or `each()` loops.
- Collection methods do not trigger lazy loading.

**Patterns:**

```php
// Bad — N+1
foreach ($users as $user) {
    $user->posts->count();
}

// Good — eager loading
$users = User::with('posts')->get();

// Good — withCount for aggregates
$users = User::withCount('posts')->get();
// Access via $user->posts_count
```

---

## 4. Index Design

**Do:**
- Index columns used in `WHERE`, `JOIN`, `ORDER BY`, `GROUP BY`.
- Composite index order must match query filter/sort (left-to-right rule).
- Use covering indexes when a query can be satisfied from the index alone.
- Aim for 3–5 well-chosen indexes per table.

**Do not:**
- Create indexes on low-cardinality columns alone.
- Keep redundant single-column indexes when a composite index covers them.
- Over-index — each index slows down writes.
- Place nullable columns first in composite indexes.

**Composite index rule:**

```sql
-- Query filters by (user_id, status) and sorts by created_at
-- Index must match this order:
ALTER TABLE orders ADD INDEX idx_user_status_created (user_id, status, created_at);
```

---

## 5. Query Optimization

**Do:**
- Use SARGable WHERE clauses — no functions on indexed columns.
- Prefer ranges: `col BETWEEN ? AND ?` instead of `DATE(col) = ?`.
- Use seek pagination (`WHERE id > ? LIMIT ?`) instead of OFFSET for large datasets.
- Push filtering, sorting, and aggregation into SQL — not PHP.
- Select only needed columns — never `SELECT *`.
- Use parameterized queries — never concatenate user input.

**Do not:**
- Use `DATE(col)`, `LOWER(col)`, `col + 1` in WHERE on indexed columns.
- Use negative conditions (`<>`, `!=`, `NOT IN`, `NOT LIKE`) — they break index usage.
- Filter or sort large collections in PHP when SQL can do it.

**Laravel patterns:**

```php
// Bad — function on indexed column
User::whereRaw('DATE(created_at) = ?', ['2025-01-01'])->get();

// Good — SARGable range
User::whereBetween('created_at', ['2025-01-01 00:00:00', '2025-01-01 23:59:59'])->get();

// Bad — OFFSET pagination
User::paginate(25); // page 1000 scans 25000 rows

// Good — seek/cursor pagination
User::where('id', '>', $lastId)->orderBy('id')->limit(25)->get();
// Or Laravel cursor pagination
User::orderBy('id')->cursorPaginate(25);
```

---

## 6. Large Data Processing

**Check:**
- `get()` is not used on potentially large result sets.
- `chunk()` or `cursor()` is used for batch processing.
- Heavy or long-running work runs in queued jobs, not in requests.
- Batch size is bounded and tuned (200–500 for `chunk()`).

**When to use which:**
- `chunk(size)` — bulk updates, batch work; bounded memory; multiple queries.
- `cursor()` — read-only iteration (exports); single row at a time; one query.
- `lazy(size)` — like cursor but with chunked fetching; good for large reads.

```php
// Bad — loads everything into memory
$users = User::all();

// Good — chunked processing
User::where('active', true)->chunk(500, function ($users) {
    foreach ($users as $user) {
        // process
    }
});

// Good — cursor for exports
User::where('active', true)->cursor()->each(function ($user) {
    // stream to CSV
});
```

---

## 7. Transactions and Locking

**Do:**
- Keep transactions short — no API calls or heavy logic inside.
- Batch related writes in a single transaction.
- Use appropriate isolation levels for the use case.

**Check:**
- No N+1 or slow queries inside transactions.
- Deadlock-prone operations use retry logic.
- `SHOW ENGINE INNODB STATUS` is used to diagnose lock waits.

```php
// Short, focused transaction
DB::transaction(function () use ($order, $items) {
    $order->save();
    $order->items()->createMany($items);
});
```

---

## 8. Schema Design

**Check:**
- Primary keys on every table.
- Fitting data types (`INT`, `DECIMAL`, `VARCHAR(n)`, `TIMESTAMP`).
- InnoDB engine.
- `lower_case_snake_case` naming.
- Normalized unless denormalization is justified by read performance.
- Partition large tables by range where beneficial.

**Migrations:**
- Only `up()` methods.
- Non-blocking index creation on large tables where supported.
- Keep migration transactions short.
- Do not chain migration commands in one shell line.

---

## 10. Advanced SQL Patterns

**Do:**
- Use CTEs (Common Table Expressions) for complex multi-step queries — improve readability over nested subqueries.
- Use window functions (`ROW_NUMBER`, `RANK`, `LAG`, `LEAD`) for analytics and ranking without self-joins.
- Use recursive CTEs for hierarchical data (categories, org trees).
- Prefer `EXISTS` over `COUNT(*)` for existence checks — stops at first match.
- Use set-based operations over row-by-row processing (cursors, loops).

**Patterns:**

```sql
-- CTE for multi-step logic
WITH active_users AS (
    SELECT id, name, email
    FROM users
    WHERE status = 'active'
)
SELECT au.name, COUNT(o.id) AS order_count
FROM active_users au
JOIN orders o ON o.user_id = au.id
GROUP BY au.id, au.name;

-- Window function for ranking
SELECT
    user_id,
    amount,
    ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY amount DESC) AS rank_num
FROM orders;

-- EXISTS over COUNT
-- Bad
SELECT * FROM users WHERE (SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id) > 0;

-- Good
SELECT * FROM users WHERE EXISTS (SELECT 1 FROM orders WHERE orders.user_id = users.id);

-- Recursive CTE for hierarchical data
WITH RECURSIVE category_tree AS (
    SELECT id, name, parent_id, 0 AS depth
    FROM categories
    WHERE parent_id IS NULL
    UNION ALL
    SELECT c.id, c.name, c.parent_id, ct.depth + 1
    FROM categories c
    JOIN category_tree ct ON c.parent_id = ct.id
)
SELECT * FROM category_tree;
```

**Laravel equivalents:**

```php
// Subquery for EXISTS
User::whereExists(function ($query) {
    $query->select(DB::raw(1))
        ->from('orders')
        ->whereColumn('orders.user_id', 'users.id');
})->get();

// Or with relationship
User::whereHas('orders')->get();
```

---

## 11. Output

**Deliver:**
- Baseline performance metrics (EXPLAIN output, query time).
- Identified bottlenecks with root cause analysis.
- Specific optimizations: index changes, query rewrites, schema improvements.
- Implementation SQL and Laravel code.
- Validation queries to measure improvement.
- Before/after EXPLAIN comparison.
