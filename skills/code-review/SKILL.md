---
name: code-review
description: Senior PHP code reviewer. Use when reviewing pull requests, examining code changes vs master branch, or when the user asks for a code review. Read-only review — never modifies code.
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/review-only.mdc
- Always apply @skills/smartest-project-addition/SKILL.md internally to identify one highest-impact, low-risk addition candidate; include it only if it maps to a real finding and keep the final output in the required findings-only format.
- All CR output (findings, recommendations, comments) must be written in English.
- Identify changes vs main branch (list commits).
- Understand context before reviewing
- Every CR must use @skills/security-review/SKILL.md for the current changes.
- Check for any points where the current changes could break the logic. If it is shared functionality, make sure to check these parts of the application as well!

**Steps:**
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- Before writing findings, collect previous CR reports from the related PR/issue discussion and build a dedup list by problem signature (file/scope + risk + root cause). Do not repeat already reported findings unless severity or impact changed.
- **Plan Alignment Analysis:** Compare the implementation against the original issue description, planning documents, or step description. Identify deviations from the planned approach, architecture, or requirements. Assess whether deviations are justified improvements or problematic departures. Verify that all planned functionality has been implemented — list any missing or only partially met items.
- **Regression analysis:** For every changed file, check whether the modifications could break existing functionality that is NOT part of the ticket scope. Trace callers and dependents of changed methods/classes. If a change alters shared logic (helpers, services, traits, base classes, interfaces), verify that all consumers still behave correctly. Flag any regression risk as a finding — even if the new code is correct in isolation, breaking unrelated features is **Critical**.
- **Security review (every CR):** Always apply @skills/security-review/SKILL.md for the current changes.
- All changes must comply with `rules/**/*.mdc`.
- Apply @rules/architecture-patterns.mdc
- **SQL analysis (only when changes touch the database):** If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed files), use @skills/mysql-problem-solver/SKILL.md for systematic analysis of those parts (identify query, inspect schema, EXPLAIN, evaluate indexes, propose safe optimizations). If there are no such changes, skip this step.
- **Race condition review (when shared state is modified):** If the changes contain any of the following signals — read-modify-write sequences, shared counters/balances/stock/quotas, `firstOrCreate`/`updateOrCreate`, retried or re-dispatched jobs that mutate shared records, cache write-back patterns, or bulk read-then-write operations — apply @skills/race-condition-review/SKILL.md. If none of these signals are present, skip this step.
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
- Services: hold business logic; return DTOs or models.
- **DTO attribute syntax (**Moderate**):** If a Spatie Laravel Data DTO overrides `from()` solely to rename input keys, or uses manual array mapping instead of `#[MapInputName(SnakeCaseMapper::class)]` / `#[MapName(SnakeCaseMapper::class)]` attributes, flag as **Moderate** and suggest the declarative attribute approach. Custom named static constructors (e.g. `fromModel()`, `fromRequest()`, `fromArray()`) that perform domain-specific data transformation beyond simple key renaming are a valid pattern and must not be flagged.
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
- **I/O bottleneck review (when changes touch file, storage, or external I/O):** If the changes include any of the following signals — synchronous file reads/writes (`file_get_contents`, `fread`, `file_put_contents`) on large or unbounded files, blocking HTTP calls without timeouts, storage operations (`Storage::put`, `Storage::get`, S3 uploads/downloads) executed in the request lifecycle, large file responses not using `StreamedResponse` or `Storage::download()`, or export/import operations loading all records into memory — flag each occurrence and recommend the appropriate async/streaming pattern. If none of these signals are present, skip this step.
- **I/O checklist:** (a) File reads/writes on large files must use PHP streams (`fopen`/`fread` in chunks) or Laravel `Storage` streaming methods. (b) Storage uploads triggered during HTTP requests must be deferred to a queued job unless the file is small (< 1 MB) and the response depends on the result. (c) Blocking HTTP calls must have explicit timeouts; consider async via queued jobs for non-critical paths. (d) File downloads must stream content with `StreamedResponse` or `Storage::download()` — never load the full file into memory. (e) CSV/Excel exports must use chunked queries (`chunk()` or `cursor()`) and stream output row by row. (f) Image or media processing (resize, compress, convert) must be offloaded to a background job.
- Memory: unresolved references, uncleared timers/listeners/closures; for large datasets ensure chunk/cursor (not `get()`) and bounded batch size.
- Scalability: locking, queue depth, missing caching for hot paths, data structures or algorithms that do not scale with volume.
- Naming: purpose-revealing; PascalCase/camelCase/kebab-case per type.
- Single responsibility; DTOs not `array<mixed>`; DRY; clear interfaces; no magic numbers (use constants).
- **`?array` is forbidden (**Critical**):** Any use of `?array` as a type hint is an error. Replace with a typed collection, DTO, or explicit `array<Type>|null`. Vague nullable arrays hide structure and break static analysis.
- **PHP array key type safety (**Moderate**):** When reviewing associative arrays, check whether a supposed string key can actually become an integer key at runtime. PHP silently casts: decimal integer strings like `'123'` → `123`; `bool` → `0`/`1`; `float` → truncated `int`; `null` → `''`. Do not trust `(string) $value` alone as proof of safety. Flag these high-risk patterns: `$map[$id] = $value;`, `$set[$value] = true;`, `$grouped[$key][] = $item;`, `$indexed[(string) $something] = ...;`. Be extra careful when the key originates from request input, database values, CSV/XML/API data, `substr()`, `trim()`, `explode()`, casts, or values typed as `mixed`, `scalar`, `string|int`, `bool`, `float`, or `null`. Pay extra attention to dangerous follow-up operations — `array_merge()`, `array_keys()`, `in_array(..., $keys, true)`, `array_key_exists()`, `isset($map[$key])`, `foreach ($map as $key => $value)` — when `$key` is later passed into a strict `string` parameter. When reporting: identify the exact risky key source, explain how PHP may cast it at runtime, state the practical impact (overwritten entries, failed strict comparisons, unexpected reindexing, possible `TypeError`), and recommend the smallest safe fix first. Suggest tests for: numeric-string keys, key collision after casting, strict lookups via `array_keys()`, `array_merge()` behaviour with casted keys.
- **Invokeable call syntax (**Moderate**):** If code calls an Action (or any invokeable class) via `->__invoke()` instead of direct invocation `$action(...)`, flag as **Moderate** and recommend the shorter form.
- Do not re-check style, types, or issues that PHPStan/Rector/PHPCS/Pint already report.
- Unnecessary complexity; large functions; repeated logic; oversized classes; mixed responsibilities.
- Recommend: simplify structure, improve cohesion, split large units.
- Rank issues by impact (highest technical debt first) when listing findings.
- Explicitly detect and report **DRY violations** (duplicated logic, duplicated validation rules, repeated branching/condition blocks, and copy-pasted code paths) as findings with actionable refactoring recommendations.
- Issues static analysis may not fully trace: business-logic flaws, missing authorization checks, data flow to sensitive sinks.
- Coverage for changed files only (target 100% for changes). Run tests only for changed files.
- New code is tested: arrange-act-assert; error cases first; descriptive names; data providers via argument; mock only external services.
- Identify missing test variations.
- For new or changed behavior, suggest concrete test scenarios where coverage is missing or unclear (e.g. "Unit: method X with null/empty input"; "Integration: POST without auth must return 401"). This supports testing readiness alongside coverage metrics.
- Laravel: prefer `Http::fake()` over Mockery.

**Deliver:** Output **only findings** (bugs/issues/risks) with a brief suggested fix. No summary, no “what was checked”, no praise.
- Use exactly three severity levels for every finding: **Critical**, **Moderate**, **Minor**.
- Group output by severity (Critical → Moderate → Minor).
- Each finding must include: **location** (file + line, or at least file), **impact/risk**, and a **concrete fix recommendation** (include a short snippet for simple fixes).
- If there are no findings, simply state that no issues were found.

**Communication protocol:**
- Do not include positive feedback or “well done” passages; output must contain only findings.
- If you find significant deviations from the requirements/specification, list them as findings with severity and recommendation.
- For implementation problems, provide clear steps to fix (and a short code example when it speeds up the fix).

**Review best practices:**
- Give concrete fixes or code snippets where relevant; not only “something is wrong”.
- Evaluate code in project context and against `rules/**/*.mdc`.
- Findings are recommendations; final decisions remain with the human reviewer.

**After completing the tasks**
- If all **Critical** and **Moderate** findings from the current CR cycle are resolved, then (and only then) run @skills/test-like-human/SKILL.md when the changes can be tested.
