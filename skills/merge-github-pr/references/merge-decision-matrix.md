# Merge Decision Matrix

## Pre-merge checklist

| Check | How to verify | Block merge? |
|---|---|---|
| PR has no conflicts | `mergeable` != `CONFLICTING` in `pr-detail.sh` output | Yes |
| CI is green | `ci_all_passed` == `YES` in `list-candidates.sh` or all `statusCheckRollup` conclusions are `SUCCESS` | Yes (unless GitHub Actions quota exceeded) |
| No unresolved review threads | Run `review-threads.sh` and `pr-reviews.sh`; check for `CHANGES_REQUESTED` state without a subsequent `APPROVED` | Yes |
| All CR findings resolved | Cross-reference review comments against current PR diff | Yes |

## Merge strategy

- **Preferred:** `--rebase` (linear history, no merge commits)
- **Fallback:** if rebase fails due to conflicts, do NOT force merge; report and stop

## GitHub Actions quota exceeded

If CI checks show `conclusion: "ACTION_REQUIRED"` or `conclusion: "CANCELLED"` and the Actions usage page indicates quota exhaustion, the CI gate may be bypassed. Verify manually that the last successful run on the same commit SHA passed all checks.

## Script execution order

1. `list-candidates.sh` — identify all open PRs and their readiness
2. For each candidate with `ci_all_passed == "YES"` and `mergeable != "CONFLICTING"`:
   a. `pr-detail.sh <PR>` — load full context
   b. `pr-reviews.sh <PR>` — load review submissions
   c. `review-threads.sh <PR>` — load inline code comments
   d. Build checklist from reviews; verify all items resolved in diff
   e. `merge-pr.sh <PR>` — rebase and merge
