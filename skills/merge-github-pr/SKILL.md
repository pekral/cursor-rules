---
name: merge-github-pr
description: "Use when merging PRs that are ready for deployment, one by one."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Never merge PRs that have conflicts
- Prefer **rebase and merge** (`--rebase`) over squash or merge commits to keep linear history.

**Scripts:** Use the pre-built scripts in `@skills/merge-github-pr/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/list-candidates.sh` | List open PRs with CI status, review decision, and conflict info |
| `scripts/pr-detail.sh <PR>` | Full PR context: body, reviews, comments, CI checks |
| `scripts/pr-reviews.sh <PR>` | Review submissions (approvals, change requests) |
| `scripts/review-threads.sh <PR>` | Inline code review comments (threads) |
| `scripts/merge-pr.sh <PR>` | Rebase-merge the PR and delete the branch |

**References:** See `@skills/merge-github-pr/references/merge-decision-matrix.md` for the decision matrix and script execution order.

**Steps:**
1. Run `scripts/list-candidates.sh` to identify all open PRs and their readiness.
2. For each candidate PR where CI passed and there are no conflicts:
   a. Run `scripts/pr-detail.sh <PR>` to load full context.
   b. Run `scripts/pr-reviews.sh <PR>` and `scripts/review-threads.sh <PR>` to load all review comments and requested changes (including unresolved/outdated discussion threads).
   c. Create a checklist of required fixes from code review.
   d. Verify that every checklist item is fully resolved in the current PR diff.
   e. If at least one item is not resolved, DO NOT merge. Report unresolved items and stop processing that PR.
3. Only when all checklist items are resolved and CI is green, run `scripts/merge-pr.sh <PR>` to rebase-merge.
4. If CI fails on GitHub Actions, check whether the Actions quota has been exceeded; if so, the CI gate may be bypassed per the decision matrix.
