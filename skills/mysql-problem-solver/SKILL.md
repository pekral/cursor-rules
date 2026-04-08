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

**Scripts:** Use the pre-built scripts in `@skills/mysql-problem-solver/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/discover-db-credentials.sh` | Find DB credentials from .env, docker-compose, and config files |
| `scripts/show-table-info.sh <table>` | Show table structure, columns, and indexes |
| `scripts/run-explain.sh "<query>" [format]` | Run EXPLAIN on a query (format: traditional or json) |

---

**References:**
- `references/investigation-workflow.md` — the 8-step investigation workflow from query identification to final report
- `references/common-mysql-problems.md` — checklist of common MySQL performance issues to detect
- `references/index-advice-rules.md` — rules for realistic, safe index recommendations
- `references/laravel-guidance.md` — Laravel-specific inspection points and common pitfalls
- `references/terminal-connection.md` — how to discover credentials and connect to MySQL

---

**Examples:** See `examples/` for expected output format:
- `examples/report-full-analysis.md` — complete analysis with DB access and EXPLAIN
- `examples/report-no-db-access.md` — static analysis without database access
- `examples/report-laravel-n-plus-one.md` — Laravel N+1 detection and fix

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

## Steps

1. Discover DB credentials using `scripts/discover-db-credentials.sh` if terminal access is available.
2. Follow the investigation workflow in `references/investigation-workflow.md` step by step.
3. Check for common problems per `references/common-mysql-problems.md`.
4. Apply index advice rules per `references/index-advice-rules.md` when suggesting changes.
5. If the input is Laravel code, also apply `references/laravel-guidance.md`.
6. Use `scripts/show-table-info.sh` and `scripts/run-explain.sh` for live inspection when DB access is available.
7. Produce the final report following the output contract below.

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

## Output contract

For each analyzed query or code path, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Query under review | Yes | The SQL query or code path being analyzed |
| Tables inspected | Yes | List of tables reviewed with row counts if known |
| Existing indexes | Yes | Indexes found on relevant tables |
| EXPLAIN summary | If DB access available | Key columns from EXPLAIN output |
| Problems found | Yes | List of detected issues |
| Recommended optimizations | Yes | Numbered list with reasons |
| Suggested SQL or code rewrite | If applicable | Improved query or code |
| Suggested index changes | If applicable | DDL or migration snippet |
| Risks and trade-offs | Yes | Write overhead, caveats |
| Confidence / limitations | Yes | High/medium/low with explanation of what was or was not verified |

If terminal access or DB credentials are unavailable, explicitly say that `EXPLAIN` and live schema verification could not be performed.

---

## Success criteria

A good result from this skill should:

- identify the real bottleneck instead of giving generic SQL advice
- validate schema and index usage whenever possible
- use `EXPLAIN` through available terminal tools
- provide actionable optimization steps
- avoid fake certainty
- stay consistent with a senior-engineer review style

**After completing the tasks**
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
