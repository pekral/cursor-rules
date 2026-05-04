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
- missing or ineffective indexes
- non-SARGable filters
- poor sort/group plans
- offset pagination on large datasets
- N+1 behavior from application code
- per-row queries inside loops — per-row `update()` / `create()` / `delete()` or single-row reads driven by a `foreach` (distinct from N+1 eager-loading: this is application code intentionally writing or reading row-by-row when a single batch query would suffice)
- redundant or overlapping indexes

### 5. Propose Optimizations
Recommend only justified changes, such as:
- query rewrite
- Eloquent/query builder rewrite
- eager loading change
- pagination change
- batching per-row loops into a single bulk operation — ModelManager batch methods (`batchUpdate`, `batchInsert`), `whereIn(...)->delete()` for deletes, or one bulk read keyed in memory for lookups (see `@rules/sql/optimalize.mdc` "Batch over per-row operations")
- index addition or replacement
- redundant index removal
- splitting one query into smaller ones

Explain trade-offs:
- write overhead
- duplicate indexes
- over-indexing
- complexity vs benefit

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
