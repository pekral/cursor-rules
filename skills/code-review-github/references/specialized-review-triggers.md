# Specialized Review Triggers

## Always applied

The following sub-skills are always applied during code review:
- `@skills/code-review/SKILL.md` — general code quality review
- `@skills/security-review/SKILL.md` — security review
- `@skills/smartest-project-addition/SKILL.md` — internally identify one highest-impact, low-risk addition candidate; include only if it maps to a real finding

## Database review

Apply `@skills/mysql-problem-solver/SKILL.md` when changes include **any** of:
- Database migrations or schema changes
- Repository classes or data access layers
- Raw SQL or query builder usage
- ORM/Eloquent queries in changed code

If none of these signals are present, skip the database review.

## Race condition review

Apply `@skills/race-condition-review/SKILL.md` when changes contain **any** of:
- Read-modify-write sequences
- Shared counters, balances, stock, or quotas
- `firstOrCreate` / `updateOrCreate` patterns
- Retried or re-dispatched jobs that mutate shared records
- Cache write-back patterns
- Bulk read-then-write operations

If none of these signals are present, skip the race condition review.

## I/O bottleneck review

Flag occurrences and recommend async/streaming patterns when changes include **any** of:
- Synchronous file reads/writes on large or unbounded files
- Blocking HTTP calls without timeouts
- Storage operations executed in the request lifecycle
- Large file responses not streamed
- Export/import operations loading all records into memory

If none of these signals are present, skip the I/O bottleneck review.

## Simplification analysis

Evaluate whether the solution can be written more simply without altering new logic, leveraging rules and conventions in `rules/**/*.mdc`. Flag unnecessary complexity as a finding.
