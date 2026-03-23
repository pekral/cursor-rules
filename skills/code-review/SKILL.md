---
name: code-review
description: Senior PHP code reviewer. Use when reviewing pull requests, examining code changes vs master branch, or when the user asks for a code review. Read-only review — never modifies code.
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- If this skill generates a Code Review report, always load existing review reports/comments first (if available) and never repeat the same previously reported finding.
- Switch to the main branch and make sure you have the updated main branch. Then switch to the branch where the PR is and, to be on the safe side, update the branch for the PR as well, then continue with the code review.
- Identify changes vs main branch (list commits).
- Understand context before reviewing
- All messages formatted as markdown for output.
- NEVER CHANGE THE CODE! Generate the output only.
- Every CR must use @.cursor/skills/security-review/SKILL.md for the current changes.
- Check for any points where the current changes could break the logic. If it is shared functionality, make sure to check these parts of the application as well!

**Steps:**
- Read project.mdc file
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- Before writing findings, collect previous CR reports from the related PR/issue discussion and build a dedup list by problem signature (file/scope + risk + root cause). Do not repeat already reported findings unless severity or impact changed.
- **Plan Alignment Analysis:** Compare the implementation against the original issue description, planning documents, or step description. Identify deviations from the planned approach, architecture, or requirements. Assess whether deviations are justified improvements or problematic departures. Verify that all planned functionality has been implemented — list any missing or only partially met items.
- **Security review (every CR):** Always apply @.cursor/skills/security-review/SKILL.md for the current changes.
- All changes must comply with `.cursor/rules/**/*.mdc`.
- **All business logic is allowed only in classes that follow the action pattern!**
- **Action pattern (only when `vendor/pekral/arch-app-services` exists):** Apply @.cursor/skills/refactor-entry-point-to-action/SKILL.md rules when reviewing PHP entry points (controllers, jobs, commands, listeners, **Livewire components**). If a new or changed entry point contains orchestration logic without an Action class, flag it as **Critical**.
- **Livewire component structure (only in Livewire projects):** Livewire components must be split into a PHP class (`app/Livewire/`) and a Blade view (`resources/views/livewire/`). Single-file (Volt) components are forbidden — flag as **Critical**. Business logic in Livewire component methods must be delegated to Action classes — flag inline business logic as **Critical**.
- **Data Validator pattern (only when `vendor/pekral/arch-app-services` exists):** If an Action class throws `ValidationException` directly or calls `Validator::make()` inline instead of delegating to a dedicated Data Validator class, flag it as **Critical**. Validation logic must be encapsulated in `app/DataValidators/{Domain}/` classes.
- **BaseModelService pattern (only when `vendor/pekral/arch-app-services` exists):** All services that primarily work with a specific Eloquent Model must extend `BaseModelService` and implement `getModelManager()`, `getRepository()`, and `getModelClass()`. If a service works with a model but does not extend `BaseModelService`, flag it as **Critical**. If a service does not primarily serve a single model but exists as a plain service class, flag it as **Moderate** and recommend refactoring to an Action pattern class.
- **SQL analysis (only when changes touch the database):** If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed files), use @.cursor/skills/mysql-problem-solver/SKILL.md for systematic analysis of those parts (identify query, inspect schema, EXPLAIN, evaluate indexes, propose safe optimizations). If there are no such changes, skip this step.
- **Race condition review (when shared state is modified):** If the changes contain any of the following signals — read-modify-write sequences, shared counters/balances/stock/quotas, `firstOrCreate`/`updateOrCreate`, retried or re-dispatched jobs that mutate shared records, cache write-back patterns, or bulk read-then-write operations — apply @.cursor/skills/race-condition-review/SKILL.md. If none of these signals are present, skip this step.
- When the task has stated requirements or acceptance criteria (from the issue/PR), verify each item against the changes; list any that are not addressed or only partially met.
- Understand what has changed and pay attention to the structural quality of the code defined in the rules.
- Ensure SRP in each class and apply SOLID principles so that the code is readable for developers.
- **Type safety and defensive programming:** Check for proper error handling robustness, type safety, and defensive programming patterns. Verify guard clauses, null checks, and safe return types.
- Do not duplicate their checks: types, null safety, formatting, style, naming, dead code, automated refactors.
- Do not review: formatting, import order, lint violations, simple typos — tools cover these.
- Focus only on what tools do not cover: architecture, design, security logic, runtime/operational concerns.
- Optimizations for processing large amounts of data
- Security risks
- Performance
- Provide categorized, actionable feedback
- Current changes must be covered by tests with 100% coverage!
- Provide specific, actionable feedback
- Include code examples in suggestions
- Praise good patterns
- Use exactly three severity levels for every finding: **Critical**, **Moderate**, **Minor**. Assign each finding to one level.
- Prioritize feedback (Critical → Moderate → Minor)
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
- **Validation rules as traits:** Reusable validation rules must be stored as traits in `App\Concerns`. Duplicated rule arrays across FormRequests should be flagged as **Moderate**.
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
- **`?array` is forbidden (**Critical**):** Any use of `?array` as a type hint is an error. Replace with a typed collection, DTO, or explicit `array<Type>|null`. Vague nullable arrays hide structure and break static analysis.
- **Invokeable call syntax (**Moderate**):** If code calls an Action (or any invokeable class) via `->__invoke()` instead of direct invocation `$action(...)`, flag as **Moderate** and recommend the shorter form.
- Do not re-check style, types, or issues that PHPStan/Rector/PHPCS/Pint already report.
- Unnecessary complexity; large functions; repeated logic; oversized classes; mixed responsibilities.
- Recommend: simplify structure, improve cohesion, split large units.
- Rank issues by impact (highest technical debt first) when listing findings.
- Issues static analysis may not fully trace: business-logic flaws, missing authorization checks, data flow to sensitive sinks.
- Coverage for changed files only (target 100% for changes). Run tests only for changed files.
- New code is tested: arrange-act-assert; error cases first; descriptive names; data providers via argument; mock only external services.
- Identify missing test variations.
- For new or changed behavior, suggest concrete test scenarios where coverage is missing or unclear (e.g. "Unit: method X with null/empty input"; "Integration: POST without auth must return 401"). This supports testing readiness alongside coverage metrics.
- Laravel: prefer `Http::fake()` over Mockery.

**Deliver:** Brief summary: issues, risks, improvements. No code changes. Use exactly three severity levels (**Critical**, **Moderate**, **Minor**) for each finding; end with a one-line summary (e.g. "Summary: 1 Critical, 2 Moderate, 3 Minor").
- Output format must match the target tracker requirements:
  - GitHub/Markdown outputs: use clear markdown sections with severity grouping.
  - JIRA outputs: use JIRA Wiki Markup (no markdown headings/fences/tables).
- For simple fixes, include a short code suggestion/snippet as part of the recommendation.

**Communication protocol:**
- Always acknowledge what was done well before highlighting issues.
- If you find significant deviations from the plan or requirements, explicitly flag them and ask for confirmation.
- If you identify issues with the original plan or requirements themselves, recommend updates.
- For implementation problems, provide clear guidance on fixes needed with code examples.

**Review best practices:**
- Give concrete fixes or code snippets where relevant; not only “something is wrong”.
- Evaluate code in project context and against `.cursor/rules/**/*.mdc`.
- Findings are recommendations; final decisions remain with the human reviewer.

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
