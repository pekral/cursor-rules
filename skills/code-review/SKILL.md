---
name: code-review
description: "Performs read-only PHP code review for pull requests or code changes vs main branch. Use when reviewing PRs, comparing changes to master, or when the user asks for a code review. Do not use for implementing fixes, writing code, or for non-PHP projects; always run security review per the security-review skill."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Use the same language as the assignment.
- Format all output as markdown.
- Do not change code; produce review output only.
- Identify changes vs main branch (list commits). Understand context before reviewing.
- Always perform a security check using the security-review skill.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Ensure all changes comply with `.cursor/rules/**/*.mdc`. Assess structural quality of the code as defined in the rules.
3. Ensure SRP in each class and apply SOLID so the code is readable for developers.
4. Do not duplicate checks that tools already cover: types, null safety, formatting, style, naming, dead code, automated refactors.
5. Focus only on what tools do not cover: architecture, design, security logic, runtime/operational concerns.
6. Review: optimizations for processing large amounts of data; security risks; SQL optimizations; performance.
7. Provide categorized, actionable feedback. Ensure current changes are covered by tests with 100% coverage.
8. Provide specific, actionable feedback with code examples where relevant. Praise good patterns.
9. Prioritize feedback (critical → minor). Review tests as thoroughly as code. Check code coverage (must be 100% for changed files).
10. Assess impact on other parts of the application.
11. Prefer `chunk()` or `cursor()` over `get()` for large result sets. `chunk(size)`: use when memory must stay bounded and doing bulk updates or batch work; tune size (e.g. 200–500). `cursor()`: use for read-only iteration over very large datasets (e.g. exports); single row at a time, generator-based.
12. Do not process large collections in a single request: offload to jobs/queues, process in batches, consider rate limiting or backpressure.
13. Inside chunks/cursors: check for N+1; eager-load relations used in the loop. Prefer set-based updates over row-by-row in PHP.
14. Schema: primary keys on every table; fitting data types (INT, DECIMAL, VARCHAR(n), TIMESTAMP); InnoDB; `lower_case_snake_case`; normalized; partition large tables by range where beneficial. When reviewing schema: drop unused or redundant indexes; aim for 3–5 well-chosen indexes per table.
15. Run EXPLAIN on new or changed queries. Flag: type ALL, high rows, Using filesort, Using temporary.
16. Indexes: columns in WHERE, JOIN, ORDER BY, GROUP BY; composite index order must match query; avoid low-cardinality-only indexes; use covering indexes where useful. Never `SELECT *`. Use prepared statements or ORM; never concatenate user input into SQL.
17. Prefer set-based operations in SQL over row-by-row in application code. Avoid functions on indexed columns in WHERE (e.g. `DATE(col)`, `LOWER(col)`). Keep transactions short; batch writes in one transaction where appropriate. Use `SHOW ENGINE INNODB STATUS` to diagnose lock waits when investigating issues.
18. Controllers: slim; delegate to Services; accept FormRequest only; never `validate()` in controller. Services: hold business logic; return DTOs or models. Repositories: read-only. ModelManagers: write-only. Jobs, Events, Commands: slim; delegate to Services. New controller actions must have corresponding Request classes.
19. Check for: race conditions; cache stampede risks; backward compatibility; performance issues; security concerns; memory leaks; timezone handling; N+1 queries; unhandled or swallowed exceptions in critical paths; overly broad catch blocks; silent failures; poor logging.
20. Defensive code: timeouts, invalid input, empty responses, failed API calls. Suggest safer error paths and guard clauses.
21. N+1: relationships used in loops must be eager-loaded (`with()`, `load()`); no DB or model calls inside loops that could be batched.
22. Avoid nested loops over large data; prefer chunk/cursor and set-based or batched work; cache repeated lookups (e.g. config, reference data).
23. Long or heavy work: run in queues/jobs, not in the request; avoid blocking I/O in the hot path.
24. Memory: unresolved references, uncleared timers/listeners/closures; for large datasets ensure chunk/cursor (not `get()`) and bounded batch size.
25. Scalability: locking, queue depth, missing caching for hot paths, data structures or algorithms that do not scale with volume.
26. Naming: purpose-revealing; PascalCase/camelCase/kebab-case per type. Single responsibility; DTOs not `array<mixed>`; DRY; clear interfaces; no magic numbers (use constants).
27. Do not re-check style, types, or issues that PHPStan/Rector/PHPCS/Pint already report.
28. Flag: unnecessary complexity; large functions; repeated logic; oversized classes; mixed responsibilities. Recommend simplifying structure, improving cohesion, splitting large units. Rank issues by impact (highest technical debt first).
29. Note issues static analysis may not fully trace: business-logic flaws, missing authorization checks, data flow to sensitive sinks.
30. Coverage for changed files only (target 100%). Run tests only for changed files. New code must be tested: arrange–act–assert; error cases first; descriptive names; data providers via argument; mock only external services. Identify missing test variations. Laravel: prefer `Http::fake()` over Mockery.

**Deliver** Brief summary: issues, risks, improvements. No code changes.

**Review best practices**
- Give concrete fixes or code snippets where relevant; not only “something is wrong”.
- Evaluate code in project context and against `.cursor/rules/**/*.mdc`.
- Findings are recommendations; final decisions remain with the human reviewer.
