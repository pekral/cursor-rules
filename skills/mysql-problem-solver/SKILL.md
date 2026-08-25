---
name: mysql-problem-solver
description: Use when analyze real MySQL query and schema problems using code
  inspection, schema review, and EXPLAIN when available
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# MySQL Problem Solver

## Purpose
Investigate real MySQL performance or query design problems in existing applications.

Focus on:
- the actual query
- real schema and index usage
- EXPLAIN-based diagnosis when possible
- safe, justified optimizations

## Constraints
- Apply @rules/sql.mdc
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Be practical and direct
- Prefer investigation over assumptions
- Do not invent schema, indexes, or runtime behavior
- Do not recommend index changes without explaining why they help
- Apply `@rules/sql/optimalize.mdc` "Performance Non-Regression on Query Changes" — every proposed query rewrite must be at least as fast as the original (ideally faster); a proposal that is slower must carry the documented reason and the remaining optimization options
- If DB access is unavailable, continue with static analysis and state the limitation clearly

## Execution

### 1. Identify the Query
- Find the actual SQL or reconstruct it from Laravel/Eloquent/query builder code
- Include filters, joins, ordering, grouping, pagination, and subqueries

### 2. Inspect Schema
- Inspect relevant tables and indexes using:
    - schema output
    - migrations
    - model relationships
    - DB tools when available

### 3. Run EXPLAIN
- If MySQL access is available, run `EXPLAIN`
- Review:
    - table
    - type
    - possible_keys
    - key
    - rows
    - filtered
    - Extra

### 4. Diagnose the Problem
Look for:
- full scans
- weak join strategy
- existing index bypassed — query could hit a covering index already in the schema but the column order, a wrapping function, or extra projected columns prevent it. Preferred fix is a query rewrite (column re-ordering, SARGable rewrite, covering projection), not a new index (see `@rules/sql/optimalize.mdc` "Reuse existing indexes first").
- missing or ineffective indexes
- non-SARGable filters
- poor sort/group plans
- offset pagination on large datasets
- N+1 behavior from application code
- per-row queries inside loops — per-row `update()` / `create()` / `delete()` or single-row reads driven by a `foreach` (distinct from N+1 eager-loading: this is application code intentionally writing or reading row-by-row when a single batch query would suffice)
- redundant or overlapping indexes
- charset / collation mismatch between compared or joined columns — the implicit conversion makes the converted side non-SARGable (its index is skipped), and an `ascii` column compared against a non-ASCII value raises `ERROR 1267` instead of returning no rows
- schema-level causes of a slow plan (see `@rules/sql/optimalize.mdc` "Schema Design"): an oversized primary key copied into every secondary index, an unjustified `VARCHAR(255)` inflating sort buffers and temporary tables, `TIMESTAMP` columns converting per row by session time zone, or `FLOAT` / `DOUBLE` where `DECIMAL` belongs

### 5. Propose Optimizations

Before recommending any rewrite, capture the **baseline** of the original query (`EXPLAIN` / `EXPLAIN ANALYZE` — `type`, `key`, `rows`, `filtered`, `Extra`, measured latency when DB access is available). Every proposal must then be held against that baseline per `@rules/sql/optimalize.mdc` "Performance Non-Regression on Query Changes":

- The rewritten query must be **equal or better** on rows examined, access `type`, index usage, `filesort` / `temporary` avoidance, and latency.
- If a proposal is unavoidably **slower** than the original (e.g. a correctness fix that widens the row set), do not present it as a clean win — state **why it is slower**, list the **remaining optimization options** (or state that none exist and why), and the **trade-off that justifies it**.

Recommend only justified changes, such as:
- query rewrite to reuse an existing schema index (preferred — verify which indexes already exist via migrations / `SHOW INDEX` before proposing a new one; reorder `WHERE` / `JOIN` / `ORDER BY` columns to match an existing composite index, drop functions wrapping indexed columns, and project only columns the index already covers)
- query rewrite (general)
- Eloquent/query builder rewrite
- eager loading change
- pagination change
- batching per-row loops into a single bulk operation — ModelManager batch methods (`batchUpdate`, `batchInsert`), `whereIn(...)->delete()` for deletes, or one bulk read keyed in memory for lookups (see `@rules/sql/optimalize.mdc` "Batch over per-row operations")
- index addition or replacement (only when the existing schema cannot cover the query and EXPLAIN confirms the gap after the rewrite alternative has been ruled out)
- redundant index removal
- column type / charset / collation alignment when the mismatch is what blocks the index (`@rules/sql/optimalize.mdc` "Schema Design")
- splitting one query into smaller ones

Explain trade-offs:
- write overhead
- duplicate indexes
- over-indexing
- complexity vs benefit

### 6. Assess Deployment Safety of Schema Changes

Run this step whenever the input includes DDL — a migration file, `Schema::create` / `Schema::table` / `Schema::drop*` / `Schema::rename*`, a `DB::statement` carrying DDL, or raw `ALTER TABLE` / `CREATE INDEX`. A statement that is instant on an empty dev database can lock a populated production table, break the release still serving traffic, or fail halfway with no way back, so judge every statement against `@rules/sql/optimalize.mdc` *Deployment Safety of Schema Changes*:

- destructive change (column / table drop or rename, narrowed type, tightened `ENUM`) shipped in the same release as the code that reads the old surface — propose the expand / contract split
- blocking DDL: the change can only run as `ALGORITHM=COPY`, or an index build states no algorithm / lock mode — propose the explicit `ALGORITHM=INSTANT` / `ALGORITHM=INPLACE, LOCK=NONE` statement, or the `pt-online-schema-change` / `gh-ost` command
- `down()` missing, empty, or not restoring the prior structure, and structure changes with no `Schema::has*()` guard to survive a replay after a failed deploy
- a data backfill inside the migration — propose the DDL-only migration plus a chunked, resumable, idempotent command / queued job
- a new `NOT NULL` / `UNIQUE` / `CHECK` / foreign key on a populated table with no pre-flight — propose the counting query that finds the violating rows, then remediation, then the constraint
- a foreign key or a newly queried column with no backing index shipped in the same release — propose the exact index DDL

Report each one as a finding with its concrete artifact, the same way step 5 reports an optimization.

## Laravel-Specific Checks
When the input is Laravel code, also inspect:
- `with()` / eager loading
- `whereHas()` / nested filters
- `withCount()`
- `chunk()` vs `cursor()` vs pagination
- scopes hiding query complexity
- repeated queries in loops

## Terminal Guidance
When terminal access is available, inspect DB connection details from:
- `.env`
- `config/database.php`
- docker/dev setup

Use MySQL tools when possible for:
- `SHOW CREATE TABLE`
- `SHOW INDEX`
- `EXPLAIN`

If access fails, continue statically and say so.

## Output Format

Use the template defined in `templates/analysis-report.md`.
---

## Principles

- Focus on the real bottleneck, not generic SQL advice
- Prefer evidence from EXPLAIN over assumptions
- Validate schema and index usage before proposing changes
- Avoid unnecessary or duplicate indexes
- Explain trade-offs (read vs write cost, complexity vs benefit)
- Be concise, practical, and explicit about limitations
