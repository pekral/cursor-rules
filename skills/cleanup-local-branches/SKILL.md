---
name: cleanup-local-branches
description: "Use when cleaning up local Git branches after origin pruning. Deletes local branches whose upstream was deleted on origin (marked gone) and local branches with no origin counterpart that have been inactive for more than six months, while always protecting the current branch and the default branches. Previews every deletion before running it."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Cleanup Local Branches

## Purpose
Prune dead local branches so the working copy only keeps branches that are still alive on origin or still recently active.

Two deletion categories:
- **Gone** — local branches whose upstream tracking branch was deleted on origin (e.g. the PR was merged and the origin branch removed).
- **Stale** — local branches that have **no** counterpart on origin and have not received a commit for **more than six months**.

---

## Constraints
- Apply `@rules/git/general.mdc`
- Output must be in English
- This skill deletes **local** refs only — it never deletes, force-pushes, or modifies any branch on origin
- Never delete the currently checked-out branch
- Never delete protected branches: `main`, `master`, `develop`, `development`, `production`, `staging`, or any branch matching `release/*` or `hotfix/*`
- Always print the full deletion preview (branch, category, last commit date, merge status, planned action) **before** deleting anything
- Never rewrite history, force-push, or run `git gc` / `git reflog expire`
- Do not delete an unmerged branch automatically — keep it and report it unless the user explicitly authorizes force deletion

---

## Use when
- The user asks to clean up, prune, or "pročistit" local branches that no longer exist on origin
- Local branches piled up after their pull requests were merged and the origin branches were deleted
- The repository accumulated old experiment branches that were never pushed and are no longer needed

---

## Execution

### 1. Refresh remote state
Update remote-tracking refs and prune deleted ones so the `gone` markers and origin counterparts are accurate:

```bash
git fetch --prune origin
```

If the repository has no `origin` remote, stop and report that the skill needs an `origin` remote to decide which branches are alive.

### 2. Record protected refs
- Current branch: `git rev-parse --abbrev-ref HEAD`
- Protected set: `main`, `master`, `develop`, `development`, `production`, `staging`, plus any branch matching `release/*` or `hotfix/*`

Exclude the current branch and every protected branch from **both** candidate groups below.

### 3. Build candidate group A — gone upstream
List local branches whose upstream was deleted on origin:

```bash
git for-each-ref --format='%(refname:short) %(upstream:track)' refs/heads
```

A branch is a **gone** candidate when its `%(upstream:track)` value is exactly `[gone]`.

### 4. Build candidate group B — stale without origin counterpart
List local branches with their last commit time and upstream:

```bash
git for-each-ref --format='%(refname:short)|%(committerdate:unix)|%(upstream)' refs/heads
```

Compute the six-month cutoff timestamp:

```bash
date -v-6m +%s 2>/dev/null || date -d '6 months ago' +%s   # BSD/macOS first, GNU fallback
```

A branch is a **stale** candidate when **all** of the following hold:
- it has no upstream on origin — the `%(upstream)` field is empty (or does not start with `refs/remotes/origin/`), **and** `git ls-remote --heads origin <branch>` returns no rows (it genuinely does not exist on origin), and
- its `%(committerdate:unix)` is **older** than the cutoff timestamp from above.

A branch already captured in group A is not re-listed in group B.

### 5. Determine merge status
For every candidate, classify whether it is safe to delete fast:

```bash
git branch --merged | tr -d ' *'   # branches fully merged into the current HEAD
```

- **Merged** — appears in the merged list → safe delete with `git branch -d <branch>`.
- **Unmerged** — does not appear → would need `git branch -D <branch>` (force) and may discard unpushed commits.

### 6. Preview before deleting
Print one row per candidate with: branch name, category (`gone` / `stale`), last commit date, merge status, and the planned action. Group rows by category. Do not delete anything before this preview is shown.

- **Interactive run:** show the preview and ask the user to confirm before deleting; if the user authorizes force deletion of unmerged branches, include them, otherwise keep them.
- **Autonomous run** (e.g. invoked by another skill or a scheduled task): delete **merged** candidates automatically and **keep** every unmerged candidate, listing the kept ones in the report with the `git branch -D` command the user can run manually.

### 7. Delete
- Merged candidates: `git branch -d <branch>`
- Unmerged candidates: only `git branch -D <branch>` and only when force deletion was explicitly authorized in step 6
- Delete one branch per command so a single failure does not abort the rest; capture and report any failure

### 8. Verify and report
Confirm the deletions with `git branch -vv` and produce the report described below.

---

## Output

- **Preview** (before deletion): candidates grouped by category with branch, last commit date, merge status, and planned action.
- **Result** (after deletion):
  - Deleted branches grouped by category (`gone`, `stale`)
  - Kept branches with the reason (`protected`, `current`, `unmerged — needs force`, `still on origin`, `active within six months`)
  - Any deletion that failed, with the error

Keep the report concise and in English.

---

## Done when
- Remote state was refreshed with `git fetch --prune origin`
- Both candidate groups were computed with the protected set and the current branch excluded
- The deletion preview was shown before any branch was deleted
- Eligible branches were deleted (merged automatically; unmerged only on explicit authorization)
- The final report lists deleted and kept branches with reasons, plus any failures
