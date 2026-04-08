---
name: merge-github-pr
description: "Use when merging PRs that are ready for deployment, one by one. Evaluates CI status, review feedback, and conflicts before rebase-merging."
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

**References:**
- `references/merge-criteria.md` — definition of resolved feedback, approval validation, dependent PRs
- `references/ci-failure-policy.md` — quota vs real failure, blocking rules, allowed exceptions
- `references/review-thread-resolution.md` — extracting checklist, verifying fixes, detecting unresolved threads
- `references/merge-decision-matrix.md` — pre-merge checklist and script execution order

**Examples:** See `examples/` for expected output format:
- `examples/report-merge-ready.md` — clean merge decision
- `examples/report-blocked-by-review.md` — blocked by unresolved review
- `examples/report-blocked-by-conflict.md` — blocked by merge conflict

**Steps:**
1. Run `scripts/list-candidates.sh` to identify all open PRs and their readiness.
2. For each candidate PR where CI passed and there are no conflicts:
   a. Run `scripts/pr-detail.sh <PR>` to load full context.
   b. Run `scripts/pr-reviews.sh <PR>` and `scripts/review-threads.sh <PR>` to load all review comments and requested changes (including unresolved/outdated discussion threads).
   c. Create a checklist of required fixes from code review per `references/review-thread-resolution.md`.
   d. Verify that every checklist item is fully resolved in the current PR diff per `references/merge-criteria.md`.
   e. If at least one item is not resolved, DO NOT merge. Report unresolved items and stop processing that PR.
3. Only when all checklist items are resolved and CI is green, run `scripts/merge-pr.sh <PR>` to rebase-merge.
4. If CI fails on GitHub Actions, evaluate per `references/ci-failure-policy.md` whether the failure is quota-related and may be bypassed.

**Output contract:** For each evaluated PR, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| PR number and title | Yes | Identifies the PR |
| Decision | Yes | `merge` or `do not merge` |
| CI status | Yes | All passed / failed / quota exceeded |
| Conflicts | Yes | None / present |
| Review status | Yes | Approved / changes requested / no reviews |
| Unresolved threads | Yes | Count and list |
| Blocking reasons | If blocked | Why the PR cannot be merged |
| Confidence notes | If applicable | Caveats or assumptions (e.g., stale approval, quota bypass) |
| Next action | Yes | What should happen next |
