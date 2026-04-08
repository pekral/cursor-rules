# Investigation Workflow

Follow this workflow in order. Do not jump straight to adding indexes without inspection.

## 1. Identify the actual query

First determine exactly what query is being executed.

Look for:

- raw SQL strings
- Eloquent chains
- query builder chains
- relationship loading
- subqueries
- scopes
- dynamic filters
- `orderBy`, `groupBy`, `distinct`, `having`, `limit`, and pagination patterns

If the input is Laravel code, reconstruct the effective SQL as faithfully as possible.

## 2. Inspect table structure

If terminal or DB access is available, inspect the relevant tables before proposing changes.

Use `scripts/show-table-info.sh <table_name>` or run manually:

```bash
SHOW CREATE TABLE table_name;
DESCRIBE table_name;
SHOW INDEX FROM table_name;
```

If the codebase contains migrations, read them too, because the schema in code may reveal intent or upcoming changes.

## 3. Use EXPLAIN through terminal tools

If MySQL access is available, run `EXPLAIN` on the real query. Use `scripts/run-explain.sh "<query>"` or run manually:

```bash
mysql -e "EXPLAIN SELECT ..."
mysql -e "EXPLAIN FORMAT=TRADITIONAL SELECT ..."
mysql -e "EXPLAIN FORMAT=JSON SELECT ..."
```

If the environment supports it and the query is a `SELECT`, prefer richer output when useful.

The skill should inspect at least these parts of `EXPLAIN` output:

- table
- type
- possible_keys
- key
- key_len
- rows
- filtered
- Extra

Treat these as diagnostic signals, not absolute truth.

## 4. Evaluate index usage

Check whether the query actually benefits from existing indexes.

Review especially:

- filter columns in `WHERE`
- join keys
- columns used in `ORDER BY`
- columns used in `GROUP BY`
- composite filter patterns
- covering index opportunities
- leftmost prefix behavior in composite indexes

The skill must distinguish between:

- no index exists
- index exists but is not chosen
- index exists but only partially helps
- query shape prevents efficient index usage

## 5. Detect common MySQL problems

See `references/common-mysql-problems.md` for the full checklist of issues to look for.

## 6. Propose safe optimizations

Only after inspection should the skill propose improvements.

Possible outputs include:

- SQL rewrite
- query builder rewrite
- eager loading change
- pagination strategy change
- index addition
- composite index replacement
- redundant index removal
- splitting one query into two smaller ones
- precomputation or denormalization suggestions when justified

Every recommendation should include a reason.

## 7. Prefer realistic index advice

See `references/index-advice-rules.md` for the full set of rules.

## 8. Produce a final report

End with a practical report that includes:

- analyzed query or code path
- relevant tables
- existing indexes found
- `EXPLAIN` summary if executed
- detected issues
- recommended actions
- optional migration snippet if a new index is justified
- confidence level and limitations
