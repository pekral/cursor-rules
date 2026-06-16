---
name: git-workflow
description: "Use when choosing a Git branching strategy or handling merge vs rebase, conflicts, stashing, undoing mistakes, and release tagging — complementing the commit/PR conventions in the git rules."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Commit, PR, and merge conventions live in `@rules/git/general.mdc` — English `type(scope)` commits, lowercase, no trailing period, no push to `main`, small focused commits, `Closes #` issue linking, English PR titles, rebase-and-merge, `gh` CLI. This skill does NOT restate them.
- Branch cleanup is owned by `@skills/cleanup-local-branches/SKILL.md`. Defer to it; do not duplicate.
- PR merging is owned by `@skills/merge-github-pr/SKILL.md`. Defer to it; do not duplicate.
- This skill covers only the complementary gaps below.

## Use when
- Choosing or changing a branching strategy.
- Deciding merge vs rebase for a specific situation.
- Resolving a merge conflict.
- Stashing work in progress.
- Undoing a mistake (bad commit, wrong reset, accidental change).
- Cutting a release and tagging a version.

## Branching strategies

### GitHub Flow (simple, recommended for most)
`main` is always deployable. Branch from `main`, open a PR, merge after review and green CI, deploy. Best for SaaS and web apps with continuous deployment.

### Trunk-based (high-velocity)
Everyone integrates into `main` via very short-lived branches (1–2 days). Incomplete work hides behind feature flags. CI must pass before merge. Needs strong CI/CD and discipline.

### GitFlow (release-cycle driven)
`main` holds production code, `develop` is the integration branch, with `release/*` and `hotfix/*` branches. Heavyweight; only worth it for scheduled, regulated releases.

| Strategy | Team size | Release cadence | Best for |
|----------|-----------|-----------------|----------|
| GitHub Flow | any | continuous | SaaS, web apps, startups |
| Trunk-based | 5+ experienced | multiple/day | high-velocity teams using feature flags |
| GitFlow | 10+ | scheduled | enterprise, regulated industries |

Default to GitHub Flow unless the team has a concrete reason for another model. It aligns with the rebase-and-merge + short-focused-branches conventions in `@rules/git/general.mdc`.

## Merge vs rebase mechanics

### Merge (preserves history)
```bash
git checkout main
git merge feature/user-auth   # creates a merge commit
```
Use when preserving exact history matters or several people worked on the branch.

### Rebase (linear history)
```bash
git checkout feature/user-auth
git fetch origin
git rebase origin/main         # replays your commits on top of main
```
Use to update your local branch with the latest `main` before opening or refreshing a PR. Keeps history linear.

```bash
# only if you are the sole contributor on the branch
git push --force-with-lease origin feature/user-auth
```
Always `--force-with-lease`, never plain `--force`.

### Pull policy: sync a side branch before pulling it
`@rules/git/general.mdc` *Pull Policy* requires every non-default branch to be rebased onto the latest default branch so it always carries the newest default-branch history. The default branch is `main` on some repos and `master` on others — resolve it instead of hardcoding `origin/main` (which does not exist on a `master`-default repo and makes the command fail). Order matters: take the branch's own remote **first**, then rebase the default branch in, then force-push — do not pull again afterwards.
```bash
DEFAULT_BRANCH="$(git symbolic-ref --short refs/remotes/origin/HEAD | sed 's@^origin/@@')"
git checkout feature/user-auth
git fetch origin
git pull --rebase                     # 1) take the branch's own remote first
git rebase "origin/$DEFAULT_BRANCH"   # 2) bring the latest default branch in
# resolve conflicts if any, then: git rebase --continue
git push --force-with-lease           # 3) publish; do NOT git pull again — it would undo the rebase
```
If the rebase changed `composer.lock` (the default branch updated dependencies), reinstall before continuing so the installed packages match the new lockfile:
```bash
composer install                      # run only when composer.lock actually changed
```
The default branch itself is exempt — pull it directly with `git pull`. Read-only review skills are exempt too: they `git pull` only to read the diff and never rebase.

### Never rebase shared/public history
Do NOT rebase a branch that has been pushed and that others may have based work on, nor any protected branch (`main`, `develop`), nor already-merged history. Rebase rewrites commits and breaks everyone downstream. For published branches, fix forward with `git revert` instead.

## Conflict resolution
```bash
# 1. Trigger the conflict (or hit it during rebase/merge)
git status                       # lists conflicted files

# 2. Resolve each file. Conflict markers:
#    <<<<<<< HEAD ... ======= ... >>>>>>> feature/user-auth
#    Edit to the correct result and delete all three markers.

# Accept one whole side when appropriate:
git checkout --ours  path/to/file    # keep current branch version
git checkout --theirs path/to/file   # keep incoming version

# 3. Stage and finish
git add path/to/file
git commit            # for merge
# or
git rebase --continue # for rebase
# bail out entirely:  git merge --abort  /  git rebase --abort
```
Prevention: keep branches small and short-lived, rebase onto `main` frequently, and coordinate before touching shared files. After resolving, re-run the project checks (see below) before continuing.

## Stash workflow
```bash
git stash push -m "wip: user auth"   # shelve tracked changes
git stash push -u -m "wip"           # include untracked files
git stash list
git stash pop                        # apply newest and drop it
git stash apply stash@{2}            # apply a specific stash, keep it
git stash drop stash@{0}
```

## Undoing mistakes
```bash
# Undo the last commit, keep the changes staged
git reset --soft HEAD~1

# Undo the last commit AND discard the changes (destructive)
git reset --hard HEAD~1

# Reverse an already-pushed commit safely (public-history safe)
git revert <sha>

# Fix the last commit message
git commit --amend -m "feat(auth): correct subject"

# Add a forgotten file to the last commit (only before it is pushed)
git add forgotten-file
git commit --amend --no-edit

# Restore a single file to its committed state
git checkout HEAD -- path/to/file
```
Rule: `reset --hard` and `--amend` rewrite history — safe only on unpushed, local-only commits. Once pushed and shared, undo with `revert`.

## Semantic versioning and release tagging
`MAJOR.MINOR.PATCH`: MAJOR for breaking changes, MINOR for backward-compatible features, PATCH for backward-compatible fixes.

```bash
git tag -a v1.2.0 -m "Release v1.2.0"   # annotated tag
git push origin v1.2.0
git tag -l                               # list tags
git tag -d v1.2.0 && git push origin --delete v1.2.0   # remove a tag

# Draft release notes from the commit range
git log v1.1.0..v1.2.0 --oneline --no-merges
```
Conventional `type(scope)` subjects from `@rules/git/general.mdc` make this changelog range readable.

## Laravel .gitignore essentials
```gitignore
/vendor/
/node_modules/
.env
.env.*.local
/public/build
/storage/*.key
.phpunit.result.cache
.DS_Store
```
Never commit `.env`, the `vendor/` or `node_modules/` trees, the Vite build output in `/public/build`, or generated keys.

## Hooks
If you wire a pre-commit or pre-push hook, run the project's own checks (the `composer build` / Composer scripts and the Pest suite), not ad-hoc tooling. The hook should fail the commit on any error, mirroring CI.

## Defer to
- `@rules/git/general.mdc` — commit, PR, and merge conventions.
- `@skills/cleanup-local-branches/SKILL.md` — deleting stale local branches.
- `@skills/merge-github-pr/SKILL.md` — merging a ready PR.

## Done when
- A branching strategy is chosen with a stated reason.
- Merge vs rebase is applied correctly and no shared/public history was rebased.
- A non-default branch was synced (own remote pulled, then the resolved default branch rebased in, then force-pushed — never hardcoding `origin/main`), and `composer install` was re-run whenever that rebase changed `composer.lock`.
- Conflicts are resolved with markers removed and project checks re-run.
- Any undo used the right tool for whether the commit was pushed.
- Releases are tagged with annotated semver tags pushed to origin.
