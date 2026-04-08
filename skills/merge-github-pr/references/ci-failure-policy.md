# CI Failure Policy

## Quota failure vs real failure

| Signal | Type | Action |
|---|---|---|
| Check conclusion `FAILURE` with test/lint output | Real failure | Block merge |
| Check conclusion `CANCELLED` or `ACTION_REQUIRED` with no logs | Likely quota | Investigate |
| Check status `QUEUED` for extended time (>30 min) | Likely quota | Investigate |
| Check conclusion `SUCCESS` | Passed | Allow merge |

## Rules for CI as blocking/non-blocking

- **Blocking by default:** All CI checks must pass before merge
- **Non-blocking exception:** GitHub Actions quota exhaustion (see below)

## When merging without CI is forbidden

- Never merge without CI when the PR modifies application source code (`src/`, `app/`, `tests/`)
- Never merge without CI when the PR modifies CI configuration itself (`.github/workflows/`)
- Never merge without CI when the PR introduces new dependencies (`composer.json`, `composer.lock`)

## When exceptions may be allowed

Merging without green CI is allowed ONLY when ALL of the following are true:
1. The CI failure is confirmed to be quota-related (no actual test/lint failure in logs)
2. The PR contains only documentation, skill definitions, or non-executable files (`.md`, `.mdc`)
3. The same checks passed on a previous commit in the same PR branch with no code changes since
4. The decision is logged in the merge report output
