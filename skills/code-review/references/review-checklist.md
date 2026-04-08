# Review Checklist

## Pre-review gates

- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- Before writing findings, collect previous CR reports from the related PR/issue discussion and build a dedup list by problem signature (file/scope + risk + root cause). Do not repeat already reported findings unless severity or impact changed.

## Plan alignment analysis

- Compare the implementation against the original issue description, planning documents, or step description.
- Identify deviations from the planned approach, architecture, or requirements.
- Assess whether deviations are justified improvements or problematic departures.
- Verify that all planned functionality has been implemented — list any missing or only partially met items.
- When the task has stated requirements or acceptance criteria (from the issue/PR), verify each item against the changes; list any that are not addressed or only partially met.

## Simplification analysis

- Evaluate whether the solution can be written more simply without altering the new logic, leveraging rules and conventions already defined in `rules/**/*.mdc`.
- Flag unnecessary complexity as a finding.

## Regression analysis

- For every changed file, check whether the modifications could break existing functionality that is NOT part of the ticket scope.
- Trace callers and dependents of changed methods/classes.
- If a change alters shared logic (helpers, services, traits, base classes, interfaces), verify that all consumers still behave correctly.
- Flag any regression risk as a finding — even if the new code is correct in isolation, breaking unrelated features is **Critical**.

## Conditional reviews

### Security review (every CR)

- Always apply @skills/security-review/SKILL.md for the current changes.

### SQL analysis (only when changes touch the database)

- If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed files), use @skills/mysql-problem-solver/SKILL.md for systematic analysis.
- If there are no such changes, skip this step.

### Race condition review (when shared state is modified)

- If the changes contain any of the following signals — read-modify-write sequences, shared counters/balances/stock/quotas, `firstOrCreate`/`updateOrCreate`, retried or re-dispatched jobs that mutate shared records, cache write-back patterns, or bulk read-then-write operations — apply @skills/race-condition-review/SKILL.md.
- If none of these signals are present, skip this step.

### I/O bottleneck review (when changes touch file, storage, or external I/O)

- If the changes include any of the following signals — synchronous file reads/writes (`file_get_contents`, `fread`, `file_put_contents`) on large or unbounded files, blocking HTTP calls without timeouts, storage operations (`Storage::put`, `Storage::get`, S3 uploads/downloads) executed in the request lifecycle, large file responses not using `StreamedResponse` or `Storage::download()`, or export/import operations loading all records into memory — flag each occurrence and recommend the appropriate async/streaming pattern.
- If none of these signals are present, skip this step.
- See `references/io-bottleneck-checklist.md` for the detailed checklist.

## Focus areas

Review must focus on what automated tools do NOT cover:
- Architecture and design
- Security logic
- Runtime/operational concerns
- Business-logic flaws
- Missing authorization checks
- Data flow to sensitive sinks

## Explicitly skip

Do not review or duplicate checks for:
- Formatting, import order, lint violations, simple typos — tools cover these
- Types, null safety, style, naming, dead code, automated refactors that PHPStan/Rector/PHPCS/Pint already report

## General review concerns

- Race conditions
- Cache stampede risks
- Backward compatibility
- Performance issues
- Security concerns
- Memory leaks
- Timezone handling
- N+1 queries
- Unhandled or swallowed exceptions in critical paths; overly broad catch blocks; silent failures; poor logging
- Defensive code: timeouts, invalid input, empty responses, failed API calls. Suggest safer error paths and guard clauses
