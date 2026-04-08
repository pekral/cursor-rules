# Database Review Rules

## Schema standards

- Primary keys on every table
- Fitting data types (INT, DECIMAL, VARCHAR(n), TIMESTAMP)
- InnoDB engine
- `lower_case_snake_case` naming
- Normalized design
- Partition large tables by range where beneficial

## Index management

- When reviewing schema: drop unused or redundant indexes; aim for 3-5 well-chosen indexes per table.
- Indexes: columns in WHERE, JOIN, ORDER BY, GROUP BY; composite index order must match query; avoid low-cardinality-only indexes; use covering indexes where useful.

## Query analysis

- Run EXPLAIN on new or changed queries. Flag: type ALL, high rows, Using filesort, Using temporary. Fix "ugly duckling" plans.
- Never `SELECT *`. Use prepared statements or ORM; never concatenate user input into SQL.
- Prefer set-based operations in SQL over row-by-row in application code.
- Avoid functions on indexed columns in WHERE (e.g. `DATE(col)`, `LOWER(col)`).

## Transaction management

- Short transactions; batch writes in one transaction where appropriate.
- Use `SHOW ENGINE INNODB STATUS` to diagnose lock waits when investigating issues.
