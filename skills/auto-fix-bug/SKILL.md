---
name: auto-fix-bug
description: Fix a reported bug end-to-end: reproduce it, add a regression test, implement the minimal fix, create a new git branch, commit using Conventional Commits, push, and open a GitHub PR (prefer gh CLI). Use when the user asks to fix a bug and wants a PR created.
---

# Auto Bugfix → Branch + Commit + PR (GitHub)

## Non-negotiable outcome
When this skill is used, you MUST deliver:
1) a **new branch** with the fix,
2) at least one **commit** (Conventional Commits),
3) a **GitHub Pull Request** opened from that branch.

If you can’t create the PR automatically (missing permissions / no `gh`), push the branch and output the exact manual steps to create the PR in GitHub UI.

## Inputs you should collect from context
Prefer no back-and-forth. If missing, infer from repo conventions; otherwise ask only the minimum:
- bug description + expected behavior
- how to reproduce (steps, endpoint, failing test, error log)
- related issue/PR link (if exists)
- target base branch (default: `master` / `main` based on repo)

## Workflow (strict order)

### 1) Reproduce the bug
- Identify the failing behavior locally (tests, reproduction steps, logs).
- If a test suite exists, find the closest relevant test and run it first.

### 2) Add a regression test (tests-first when possible)
- Add/adjust a test that fails **before** the fix and passes **after** the fix.
- Keep the test minimal and focused on the reported bug.

### 3) Create a new branch
Branch naming:
- Prefer: `fix/<short-slug>` (or `bugfix/<ticket>-<slug>` if ticket id exists)
  Commands:
- `git status` (must be clean or explicitly explain what you’re doing)
- `git checkout -b fix/<slug>`

### 4) Implement the minimal fix
- Smallest change that makes the regression test pass.
- Avoid drive-by refactors unless required to fix the bug.
- Keep public APIs stable unless the bug is explicitly an API change.

### 5) Run checks locally
Run the fastest relevant subset first, then full suite when reasonable:
- tests (single file / targeted, then full)
- lints / static analysis if available
  If something fails, fix it before committing.

### 6) Commit (Conventional Commits)
Commit message rules:
- Must follow Conventional Commits.
- For bugfixes default to `fix:` (optionally `fix(scope):`).
- Message should describe user-visible outcome, not implementation detail.
- Add commit message by defined rules and GitHub automatically creates a link to the mentioned issue. For example, if your issue number is 123 , you can mention it in your PR like this: #123

Examples:
- `fix(auth): prevent token refresh race condition`
- `fix: handle null customer id in webhook handler`

Commands:
- `git add -A`
- `git commit -m "fix(<scope>): <summary>"`

### 7) Push branch
- `git push -u origin fix/<slug>`
  Never force push unless the user explicitly asked.

### 8) Create a PR in GitHub
Prefer GitHub CLI:
- `gh pr create --base <base> --head fix/<slug> --title "<title>" --body "<body>"`

PR title:
- Same as commit subject (without scope is ok), keep it short.

PR body template (fill it):
- **What/Why**: 1–3 bullets
- **Repro**: steps or link to failing case
- **Fix**: what changed
- **Tests**: exact commands run
- **Risk**: any edge cases / migration notes
- **Links**: closes #ID (if applicable)

### 9) Final verification
- Ensure PR is open and points to correct base branch.
- Ensure branch contains commit(s) and CI can run.

## Guardrails
- Never commit secrets, tokens, or credentials.
- Don’t change formatting project-wide.
- Don’t “fix” unrelated warnings opportunistically.
- If you touch production-critical paths, add extra tests.

## If `gh` is unavailable
Do:
1) Push the branch.
2) Print instructions:
    - PR URL format hint (repo + compare branch)
    - base branch to select
    - title/body to paste
