---
name: code-review
description: Senior PHP code reviewer. Use when reviewing pull requests, examining code changes vs master branch, or when the user asks for a code review. Read-only review — never modifies code.
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.claude/rules/.*mdc).
- Always apply @.claude/skills/smartest-project-addition/SKILL.md internally to identify one highest-impact, low-risk addition candidate; include it only if it maps to a real finding and keep the final output in the required findings-only format.
- I want the texts to be in the language in which the assignment was written.
- **Before starting the review**, analyze all comments and discussions in the issue so that you fully understand what the final state should be and what logic should have been created. Only then begin reviewing.
- Always load existing CR reports/comments from the issue tracker and related PR (using available CLI tools or MCP servers) before generating a new CR report, and never repeat a previously reported finding.
- Switch to the main branch and make sure you have the updated main branch. Then switch to the branch where the PR is and, to be on the safe side, update the branch for the PR as well, then continue with the code review.
- Identify changes vs main branch (list commits).
- Understand context before reviewing
- All messages formatted as markdown for output.
- NEVER CHANGE THE CODE! Generate the output only.
- Every CR must use @.claude/skills/security-review/SKILL.md for the current changes.
- Check for any points where the current changes could break the logic. If it is shared functionality, make sure to check these parts of the application as well!

**Steps:**
- Read project.mdc file
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- Before writing findings, collect previous CR reports from the related PR/issue discussion and build a dedup list by problem signature (file/scope + risk + root cause). Do not repeat already reported findings unless severity or impact changed.
- **Plan Alignment Analysis:** Compare the implementation against the original issue description, planning documents, or step description. Identify deviations from the planned approach, architecture, or requirements. Assess whether deviations are justified improvements or problematic departures. Verify that all planned functionality has been implemented — list any missing or only partially met items.
- **Security review (every CR):** Always apply @.claude/skills/security-review/SKILL.md for the current changes.
- All changes must comply with `.claude/rules/**/*.mdc`.
- **All business logic is allowed only in classes that follow the action pattern!**
- **Action pattern (only when `vendor/pekral/arch-app-services` exists):** Apply @.claude/skills/refactor-entry-point-to-action/SKILL.md rules when reviewing PHP entry points (controllers, jobs, commands, listeners, **Livewire components**). If a new or changed entry point contains orchestration logic without an Action class, flag it as **Critical**.
- **Livewire component structure (only in Livewire projects):** Livewire components must be split into a PHP class (`app/Livewire/`) and a Blade view (`resources/views/livewire/`). Single-file (Volt) components are forbidden — flag as **Critical**. Business logic in Livewire component methods must be delegated to Action classes — flag inline business logic as **Critical**.
- **Data Validator pattern (only when `vendor/pekral/arch-app-services` exists):** If an Action class throws `ValidationException` directly or calls `Validator::make()` inline instead of delegating to a dedicated Data Validator class, flag it as **Critical**. Validation logic must be encapsulated in `app/DataValidators/{Domain}/` classes.
- **BaseModelService pattern (only when `vendor/pekral/arch-app-services` exists):** All services that primarily work with a specific Eloquent Model must extend `BaseModelService` and implement `getModelManager()`, `getRepository()`, and `getModelClass()`. If a service works with a model but does not extend `BaseModelService`, flag it as **Critical**. If a service does not primarily serve a single model but exists as a plain service class, flag it as **Moderate** and recommend refactoring to an Action pattern class.
- **SQL analysis (only when changes touch the database):** If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed files), use @.claude/skills/mysql-problem-solver/SKILL.md for systematic analysis of those parts (identify query, inspect schema, EXPLAIN, evaluate indexes, propose safe optimizations). If there are no such changes, skip this step.
- **Race condition review (when shared state is modified):** If the changes contain any of the following signals — read-modify-write sequences, shared counters/balances/stock/quotas, `firstOrCreate`/`updateOrCreate`, retried or re-dispatched jobs that mutate shared records, cache write-back patterns, or bulk read-then-write operations — apply @.claude/skills/race-condition-review/SKILL.md. If none of these signals are present, skip this step.
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
- **Invokeable controller rule (**Critical**):** Any controller method that is not a standard CRUD method (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) must be a dedicated single-action invokeable controller exposing only `__invoke()`. A resource controller must not contain non-CRUD methods — flag as **Critical** if found.
- **Validation rules as traits:** Reusable validation rules must be stored as traits in `App\Concerns`. Duplicated rule arrays across FormRequests should be flagged as **Moderate**.
- Services: hold business logic; return DTOs or models.
- **DTO attribute syntax (**Moderate**):** If a Spatie Laravel Data DTO overrides `from()` solely to rename input keys, or uses manual array mapping instead of `#[MapInputName(SnakeCaseMapper::class)]` / `#[MapName(SnakeCaseMapper::class)]` attributes, flag as **Moderate** and suggest the declarative attribute approach.
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
- **Laravel AI SDK (**Critical**):** If a Laravel project implements AI features (e.g., LLM calls, embeddings, agents) without using the [Laravel AI SDK](https://laravel.com/docs/13.x/ai-sdk) — for example by calling AI provider APIs directly via raw HTTP or a non-Laravel SDK — flag it as **Critical** and recommend migrating to the Laravel AI SDK.

**Deliver:** Vypiš **pouze nálezy** (chyby/problémy/risks) se stručným návrhem řešení. Žádné shrnutí, žádné “co bylo zkontrolováno”, žádná chvála.
- Použij přesně tři úrovně závažnosti pro každý nález: **Critical**, **Moderate**, **Minor**.
- Výstup seskup podle závažnosti (Critical → Moderate → Minor).
- Každý nález musí mít: **lokaci** (soubor + řádek, nebo minimálně soubor), **dopad/riziko** a **konkrétní doporučení opravy** (u jednoduchých fixů klidně krátký snippet).
- Pokud nejsou žádné nálezy, napiš jen informaci, že nebyly nalezeny žádné problémy.

**Communication protocol:**
- Neuváděj pozitivní hodnocení ani “well done” pasáže; výstup má obsahovat jen nálezy.
- Pokud najdeš významné odchylky od zadání/požadavků, zalistuj je jako nálezy se závažností a doporučením.
- Pro implementační problémy dávej jasné kroky k opravě (a krátký příklad kódu, když to zrychlí fix).

**Review best practices:**
- Give concrete fixes or code snippets where relevant; not only “something is wrong”.
- Evaluate code in project context and against `.claude/rules/**/*.mdc`.
- Findings are recommendations; final decisions remain with the human reviewer.

**After completing the tasks**
- If all **Critical** and **Moderate** findings from the current CR cycle are resolved, then (and only then) run @.claude/skills/test-like-human/SKILL.md when the changes can be tested.
