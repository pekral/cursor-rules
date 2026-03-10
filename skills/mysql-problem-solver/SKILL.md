---
name: mysql-problem-solver
description: "Use when you need to analyze and solve MySQL performance or query design problems directly from a real codebase or database environment. Inspects SQL queries, table structure, index usage, and uses terminal tools (mysql, EXPLAIN) when possible. For pragmatic diagnosis of slow queries, joins, indexes, and filtering in existing applications."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Purpose

Use this skill when you need to analyze and solve MySQL performance or query design problems directly from a real codebase or database environment. The skill is designed for practical investigation, not theoretical advice. It should inspect SQL queries, review table structure, evaluate index usage, and use available terminal tools such as `mysql` and `EXPLAIN` whenever possible.

This skill is intended for situations where the model should behave like a pragmatic senior engineer who is diagnosing slow queries, suspicious joins, missing indexes, or poor filtering strategies inside an existing application.

---

## When to use

Use this skill when the task involves any of the following:

- checking whether a MySQL query is inefficient
- proposing a safer or faster SQL rewrite
- understanding how a query uses indexes
- loading table definitions before suggesting changes
- running `EXPLAIN` on a query through terminal tools
- reviewing joins, filtering, ordering, grouping, or pagination
- checking whether an existing index is used
- deciding whether a new index is justified
- investigating performance problems in Laravel, raw SQL, repositories, services, or migrations

This skill should be preferred when the problem is tied to **real MySQL behavior**, not only static code style.

---

## When not to use

Do not use this skill when:

- the task is purely about SQL syntax learning
- the database is not MySQL-compatible
- the user only wants a generic explanation without code or query analysis
- there is no query, schema, migration, repository, model, or terminal/database access to inspect

If the environment does not allow database access, the skill should still perform a static review, but it must clearly say that conclusions are limited because `EXPLAIN`, schema inspection, and real index verification were not executed.

---

## Inputs the skill can work with

This skill can work with one or more of the following inputs:

- a raw SQL query
- a repository or service class containing query builder code
- a Laravel Eloquent query
- a migration file
- a model and its relations
- a failing or slow endpoint description
- terminal access to MySQL
- environment variables or config that expose DB credentials
- copied output from `EXPLAIN`, `SHOW CREATE TABLE`, or `DESCRIBE`

The skill should not block on perfect input. It should inspect whatever is available and continue.

---

## Core goals

The skill must try to achieve these goals in order:

1. Understand what query or query pattern is being analyzed.
2. Load the relevant table structure if possible.
3. Identify indexes already available.
4. Run `EXPLAIN` using terminal tools if possible.
5. Detect likely performance issues.
6. Propose concrete optimizations.
7. Suggest or generate safe index changes only when justified.
8. Explain trade-offs, especially write amplification, duplicate indexes, and over-indexing.
9. If appropriate, propose a rewritten SQL query or Laravel query builder version.
10. Summarize findings in a concise report with action items.

---

## Required investigation workflow

Follow this workflow in order. Do not jump straight to adding indexes without inspection.

### 1. Identify the actual query

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

### 2. Inspect table structure

If terminal or DB access is available, inspect the relevant tables before proposing changes.

Prefer commands such as:

```bash
SHOW CREATE TABLE table_name;
DESCRIBE table_name;
SHOW INDEX FROM table_name;
```

If the codebase contains migrations, read them too, because the schema in code may reveal intent or upcoming changes.

### 3. Use EXPLAIN through terminal tools

If MySQL access is available, run `EXPLAIN` on the real query. Use terminal tools whenever possible.

Examples:

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

### 4. Evaluate index usage

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

### 5. Detect common MySQL problems

The skill should actively look for these issues:

- full table scans on large tables
- joins without effective indexes
- filtering on low-selectivity columns without a better composite index
- functions on indexed columns that make indexes unusable
- leading wildcard searches like `LIKE '%term%'`
- sorting without supporting indexes
- offset pagination on large datasets
- `OR` conditions that degrade index usage
- `SELECT *` on wide tables when fewer columns are needed
- redundant or overlapping indexes
- missing foreign key side indexes
- N+1 patterns caused by application code
- unbounded scans caused by missing limits or weak predicates

### 6. Propose safe optimizations

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

### 7. Prefer realistic index advice

When suggesting indexes, follow these rules:

- do not propose indexes blindly
- avoid duplicate or near-duplicate indexes without justification
- prefer composite indexes that match real filter and sort patterns
- mention leftmost prefix implications
- warn about insert/update overhead
- mention when a proposed index helps reads but hurts writes
- avoid recommending every filtered column as a standalone index

### 8. Produce a final report

End with a practical report that includes:

- analyzed query or code path
- relevant tables
- existing indexes found
- `EXPLAIN` summary if executed
- detected issues
- recommended actions
- optional migration snippet if a new index is justified
- confidence level and limitations

---

## Output format

Use this response structure:

```md
## MySQL Analysis Report

### Query under review
...

### Tables inspected
...

### Existing indexes
...

### EXPLAIN summary
...

### Problems found
- ...

### Recommended optimizations
1. ...
2. ...

### Suggested SQL or code rewrite
...

### Suggested index changes
...

### Risks and trade-offs
...

### Confidence / limitations
...
```

If terminal access or DB credentials are unavailable, explicitly say that `EXPLAIN` and live schema verification could not be performed.

---

## Behavior rules

The skill must behave according to these rules:

- Be practical and direct.
- Prefer investigation over assumptions.
- Use terminal tools for real verification whenever available.
- Use `EXPLAIN` whenever database access is possible.
- Read schema before recommending indexes.
- Do not invent database structure that was not observed.
- Do not claim an index is missing until you have checked schema, migrations, or user-provided index output.
- Do not recommend optimizations without explaining why they help.
- Keep recommendations scoped to the observed problem.
- If multiple queries are involved, analyze them one by one.
- If the issue appears to be application-level rather than SQL-level, say so clearly.

---

## Terminal guidance

When terminal access is available, the skill should try to discover how to connect safely to MySQL by checking:

- `.env`
- `config/database.php`
- docker compose files
- local dev scripts
- CI or docs mentioning DB access

Possible command patterns:

```bash
cat .env | grep DB_
php artisan env
php artisan tinker
mysql --version
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SHOW TABLES;"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SHOW CREATE TABLE users\G"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "EXPLAIN SELECT ..."
```

If credentials are unavailable or access fails, continue with static analysis and state that runtime verification could not be completed.

---

## Laravel-specific guidance

When the input is Laravel code, also inspect:

- eager loading opportunities via `with()`
- `whereHas()` and nested relationship filters
- `withCount()` usage
- `chunk()` vs `cursor()` vs pagination
- scopes that hide query complexity
- repeated query patterns in loops
- casts or accessors that trigger hidden queries
- whether a repository or service builds inefficient dynamic filters

If useful, the skill may provide both:

- a rewritten SQL query
- an improved Eloquent or query builder version

---

## Example prompts

```text
@.cursor/skills/mysql-problem-solver analyze this query and check whether indexes are used effectively
```

```text
@.cursor/skills/mysql-problem-solver inspect OrderRepository and use EXPLAIN if terminal access is available
```

```text
@.cursor/skills/mysql-problem-solver review this slow MySQL query, inspect table structure, and propose safe index changes
```

```text
@.cursor/skills/mysql-problem-solver analyze the Laravel query in this service, reconstruct the SQL, inspect indexes, and optimize it
```

---

## Success criteria

A good result from this skill should:

- identify the real bottleneck instead of giving generic SQL advice
- validate schema and index usage whenever possible
- use `EXPLAIN` through available terminal tools
- provide actionable optimization steps
- avoid fake certainty
- stay consistent with a senior-engineer review style
