# Example: DX Improvement Proposal

## Proposal

Add a pre-commit hook that runs type-checking and lint on staged files only, reducing feedback loop from 45s to under 5s.

## Expected Benefits

- **Business:** Fewer defects reach code review, reducing review cycle time by ~30%
- **Technical:** Catches type errors before they propagate; enforces consistent code style at commit time

## Evaluation

| Dimension | Rating |
|---|---|
| Impact | High |
| Complexity | Low |
| Risk | Low |
| Reversibility | High |

## Key Risks and Mitigations

| Risk | Mitigation |
|---|---|
| Developers bypass hooks with `--no-verify` | CI enforces the same checks as a gate |
| Hook slows down on large changesets | Scope to staged files only via `lint-staged` |

## Minimal Implementation Plan

1. Install `husky` and `lint-staged` as dev dependencies
2. Configure `lint-staged` to run type-check and lint on `*.ts` files
3. Add a `.husky/pre-commit` hook that invokes `lint-staged`
4. Verify CI runs the same checks to serve as a safety net

## Test Strategy

- Commit a file with a type error — hook should block
- Commit a clean file — hook should pass in under 5s
- Run `--no-verify` and confirm CI catches the same issue

## Rollout / Rollback

- **Rollout:** Merge to main; all developers get the hook on next `npm install`
- **Rollback:** Remove `husky` and `lint-staged` config; one commit to revert
