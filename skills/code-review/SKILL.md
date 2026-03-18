---
name: code-review
description: Senior PHP code reviewer. Use when reviewing pull requests, examining code changes vs master branch, or when the user asks for a code review. Read-only review — never modifies code.
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- Switch to the main branch and make sure you have the updated main branch. Then switch to the branch where the PR is and, to be on the safe side, update the branch for the PR as well, then continue with the code review.
- Identify changes vs main branch (list commits).
- Understand context before reviewing
- All messages formatted as markdown for output.
- NEVER CHANGE THE CODE! Generate the output only.
- Always do security check by defined skill for security!
- Check for any points where the current changes could break the logic. If it is shared functionality, make sure to check these parts of the application as well!

**Steps:**
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- All changes must comply with `.cursor/rules/**/*.mdc`.
- Read project.mdc file
- Understand what has changed and pay attention to the structural quality of the code defined in the rules.
- Ensure SRP in each class and apply SOLID principles so that the code is readable for developers.
- Do not duplicate their checks: types, null safety, formatting, style, naming, dead code, automated refactors.
- Focus only on what tools do not cover: architecture, design, security logic, runtime/operational concerns.
- Optimizations for processing large amounts of data
- Security risks
- SQL optimizations: when reviewing SQL queries, repositories, migrations, or query builder code, use @.cursor/skills/mysql-problem-solver/SKILL.md for systematic analysis (identify query, inspect schema, EXPLAIN, evaluate indexes, propose safe optimizations).
- Performance
- Provide categorized, actionable feedback
- Current changes must be covered by tests with 100% coverage!
- Provide specific, actionable feedback
- Include code examples in suggestions
- Praise good patterns
- Prioritize feedback (critical → minor)
- Review tests as thoroughly as code
- Check code coverage (must be 100% for changed files)
- Assess impact on other parts of the application.
- Prefer `chunk()` or `cursor()` over `get()` for large result sets. `get()` loads everything into memory and does not scale.
- **chunk(size):** Use when memory must stay bounded and you do bulk updates or batch work. Tune size (e.g. 200–500) to balance memory vs round-trips.
- **cursor():** Use for read-only iteration over very large datasets (e.g. exports); single row at a time, generator-based, safe under concurrent writes.
- Do not process large collections in a single request: offload to jobs/queues, process in batches, consider rate limiting or backpressure.
- Inside chunks/cursors: check for N+1; eager-load relations used in the loop. Prefer set-based updates over row-by-row in PHP.
- Primary keys on every table; fitting data types (INT, DECIMAL, VARCHAR(n), TIMESTAMP); InnoDB; `lower_case_snake_case`; normalized; partition large tables by range where beneficial.
- When reviewing schema: drop unused or redundant indexes; aim for 3–5 well-chosen indexes per table.
- Run EXPLAIN on new or changed queries. Flag: type ALL, high rows, Using filesort, Using temporary. Fix “ugly duckling” plans.
- Indexes: columns in WHERE, JOIN, ORDER BY, GROUP BY; composite index order must match query; avoid low-cardinality-only indexes; use covering indexes where useful.
- Never `SELECT *`. Use prepared statements or ORM; never concatenate user input into SQL.
- Prefer set-based operations in SQL over row-by-row in application code. Avoid functions on indexed columns in WHERE (e.g. `DATE(col)`, `LOWER(col)`).
- Short transactions; batch writes in one transaction where appropriate.
- Use `SHOW ENGINE INNODB STATUS` to diagnose lock waits when investigating issues.
- Controllers: slim; delegate to Services; accept FormRequest only; never `validate()` in controller.
- Services: hold business logic; return DTOs or models.
- Repositories: read-only. ModelManagers: write-only.
- Jobs, Events, Commands: slim; delegate to Services.
- New controller actions must have corresponding Request classes.
- Race conditions
- Cache stampede risks
- Backward compatibility
- Performance issues
- Security concerns
- Memory leaks
- Timezone handling
- N+1 queries
- Unhandled or swallowed exceptions in critical paths; overly broad catch blocks; silent failures; poor logging.
- Defensive code: timeouts, invalid input, empty responses, failed API calls. Suggest safer error paths and guard clauses.
- N+1: relationships used in loops must be eager-loaded (`with()`, `load()`); no DB or model calls inside loops that could be batched.
- Avoid nested loops over large data; prefer chunk/cursor and set-based or batched work; cache repeated lookups (e.g. config, reference data).
- Long or heavy work: run in queues/jobs, not in the request; avoid blocking I/O in the hot path.
- Memory: unresolved references, uncleared timers/listeners/closures; for large datasets ensure chunk/cursor (not `get()`) and bounded batch size.
- Scalability: locking, queue depth, missing caching for hot paths, data structures or algorithms that do not scale with volume.
- Naming: purpose-revealing; PascalCase/camelCase/kebab-case per type.
- Single responsibility; DTOs not `array<mixed>`; DRY; clear interfaces; no magic numbers (use constants).
- Do not re-check style, types, or issues that PHPStan/Rector/PHPCS/Pint already report.
- Unnecessary complexity; large functions; repeated logic; oversized classes; mixed responsibilities.
- Recommend: simplify structure, improve cohesion, split large units.
- Rank issues by impact (highest technical debt first) when listing findings.
- Issues static analysis may not fully trace: business-logic flaws, missing authorization checks, data flow to sensitive sinks.
- Coverage for changed files only (target 100% for changes). Run tests only for changed files.
- New code is tested: arrange-act-assert; error cases first; descriptive names; data providers via argument; mock only external services.
- Identify missing test variations.
- Laravel: prefer `Http::fake()` over Mockery.

**Deliver:** Brief summary: issues, risks, improvements. No code changes.

**Review best practices:**
- Give concrete fixes or code snippets where relevant; not only “something is wrong”.
- Evaluate code in project context and against `.cursor/rules/**/*.mdc`.
- Findings are recommendations; final decisions remain with the human reviewer.

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
