# Definition of Done

## Required completion criteria

All of the following must be true before the refactoring is considered complete:

| Criterion | Description |
|---|---|
| Thin entry point | Target entry point method is thin and delegates to a dedicated Action |
| Action constraints | Action respects all project Action-pattern constraints (see `action-pattern-rules.md`) |
| Internal review | Architecture-focused review was executed and all critical/medium findings were fixed |
| Clean implementation | Action implementation is clean, simple, and optimized without changing behavior |
| PHPDoc | Required PHPDoc for PHPStan is present on touched PHP code where needed |
| Test coverage | Tests cover the refactored flow including failure/edge paths where applicable |
| Quality checks | Required project quality checks pass for changed files |

## Post-refactor verification

- Run an internal architecture-first code review of the generated changes (no third-party reporting).
- If the review finds critical or medium issues, fix them immediately and repeat the review until no such findings remain.
- If PHP files changed, run required project checks/fixers and resolve all issues.
- If new database migrations were created during the changes, run them (`php artisan migrate`) before running tests or creating a PR.
