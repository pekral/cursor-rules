---
name: process-code-review
description: "Use when processing pull request code review feedback. Finds the latest PR for a task, resolves review comments, updates review status, and triggers the next review cycle."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/php/core-standards.mdc
- Apply @rules/git/general.mdc
- Apply @rules/jira/general.mdc
- Apply @rules/reports/general.mdc. **CR reply comments and resolved-items updates posted on the GitHub PR** stay in canonical English per the rule's *Exception — technical CR findings on the GitHub PR* (they extend the technical CR thread). The **mirrored non-technical summary** delegated to `@skills/pr-summary/SKILL.md` on the linked issue / JIRA ticket follows the language of the source assignment. Never mix languages inside the same comment; never use bilingual *Kritické (Critical)* style parentheses.
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Never mix two natural languages inside a single CR comment. The English exception applies to entire comments — not to inline parenthetical glosses.
- Never push direct changes to the main branch
- If the pull request has merge conflicts with the base branch, stop and report it
- Do not introduce new logic unrelated to review feedback

---

## Steps

- Identify the task from the provided issue code or URL
- Find all open pull requests for the task
  - If multiple PRs exist, process each independently
- Before processing a PR, switch to the PR branch and pull latest changes following `@rules/git/general.mdc` *Pull Policy*, in order: resolve the default branch (`DEFAULT_BRANCH="$(git symbolic-ref --short refs/remotes/origin/HEAD | sed 's@^origin/@@')"` — never hardcode `origin/main`), `git fetch origin`, `git pull --rebase` to take the PR branch's own remote first, then `git rebase "origin/$DEFAULT_BRANCH"` to bring the default branch in, resolve any conflicts, and `git push --force-with-lease`. Do not `git pull` again after the rebase — it would undo the sync. If the rebase changed `composer.lock`, run `composer install` immediately so dependencies match the new lockfile. If the rebase surfaces conflicts that cannot be resolved cleanly, stop and report it (the existing merge-conflict constraint).

### For each PR:

- Load PR context by running `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — the single deterministic entry point. Never call `gh issue view`, `gh pr view`, or `gh api /repos/.../issues/...` directly. Read review comments, files, commits, status checks, and `closingIssues` off the resulting JSON document. If the script is unavailable (missing tool, exit code 2/3) fall back to the GitHub MCP server, and always prefer the MCP fallback for review-thread / line-anchored comments that the script does not return.
- **Load unresolved reviewer threads (mandatory, GitHub).** `load-issue.sh` returns general `comments[]` and `reviews[]` but never the line-anchored review threads nor their resolved/unresolved state. Fetch them deterministically with the GraphQL `reviewThreads` connection — this is **not** one of the forbidden REST endpoints (`gh issue view`, `gh pr view`, `gh api /repos/.../issues/...`):
  ```
  gh api graphql -f query='
  query($owner:String!,$repo:String!,$number:Int!,$cursor:String){
    repository(owner:$owner,name:$repo){
      pullRequest(number:$number){
        reviewThreads(first:100, after:$cursor){
          pageInfo{ hasNextPage endCursor }
          nodes{
            id isResolved path line
            comments(first:100){ nodes{ author{login} body url createdAt } }
          }
        }
      }
    }
  }' -F owner=<owner> -F repo=<repo> -F number=<number>
  ```
  **Do not accept a truncated list** — the "every unresolved thread" guarantee depends on completeness. When `reviewThreads.pageInfo.hasNextPage` is `true`, repeat the query with `-F cursor=<endCursor>` until it is `false`; when any thread's `comments.nodes` reaches the page size, page that thread's comments the same way. If `gh api graphql` is unavailable, fall back to the GitHub MCP server for the same thread list plus its resolved state.
- Build the checklist from **both** sources:
  1. Structured CR findings published by the review skills (general comments come from `comments[]`).
  2. **Unresolved reviewer threads** from the `reviewThreads` query — add every thread where `isResolved == false` (human reviewer **and** bot) as a checklist item, and **skip every thread where `isResolved == true`**. Record each thread's `id` so it can be marked resolved once its fix lands (see **Resolve addressed reviewer threads** below).
- Map each finding to a concrete code or test change

#### Reproducer extraction (per finding)

For every Critical and Moderate finding, extract the reproducer fields published by the CR skills (`@skills/code-review/SKILL.md`, `@skills/code-review-github/SKILL.md`, `@skills/code-review-jira/SKILL.md`, `@skills/security-review/SKILL.md`):

- **Faulty Example** — the minimal snippet or input that reproduces the bug
- **Expected Behavior** — the assertion target the test must verify
- **Test Hint** — the layer (unit, integration, feature) and entry point
- **Suggested Fix** — the minimal corrected snippet that resolves the finding (may be `n/a — <reason>` when the Fix narrative is sufficient)

Read the reproducer fields off `comments[]` and `body` / `descriptionText` returned by the deterministic loader for the originating tracker instead of re-fetching the issue:
- **GitHub-originated reviews:** `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>`. Never call `gh issue view`, `gh pr view`, or `gh api /repos/.../issues/...` directly.
- **JIRA-originated reviews:** `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>`. Never call `acli` directly.

Use these to write a failing test **before** applying the fix:

1. Drop the Faulty Example into a new test case at the layer named in the Test Hint.
2. Assert the Expected Behavior — the test must fail on the current code.
3. Apply the Suggested Fix snippet (or the Fix narrative when Suggested Fix is `n/a`); rerun the test until it passes.

If a **CR-skill finding** lacks Faulty Example, Expected Behavior, or Test Hint, request a CR rerun rather than guessing — the CR skills are responsible for providing them. Suggested Fix may legitimately be `n/a` per the CR rules.

**Free-form reviewer threads are exempt from the reproducer requirement.** Unresolved threads written by human reviewers will not carry the four structured fields. Do **not** request a CR rerun for them and do **not** block. Instead, derive the intent from the comment text, apply the minimal best-effort fix that satisfies it, and add or adjust a test at your discretion (a regression test when the comment describes a behavior bug; none when it is a naming / readability / dead-code remark). Keep the change scoped strictly to what the reviewer asked for. The exemption removes only the mandatory reproducer workflow — a behavior-changing best-effort fix still has to satisfy the diff-scoped coverage gate enforced by the **Review loop** below (`@rules/php/core-standards.mdc` Testing).

---

### Pre-fix phase — pre-existing issue handling

While reading the affected files in preparation for the CR fixes, you may encounter problems that are **unrelated to the reviewer feedback** but were already present in those files. The following categories qualify:

- **Bugs** — incorrect logic, broken edge cases, null-dereference risks, race conditions, or runtime errors that exist before this CR.
- **Project-rule violations** — code that contradicts any rule listed in this skill's *Constraints* block (`@rules/php/core-standards.mdc`, `@rules/git/general.mdc`, `@rules/laravel/*`, …) or any other rule under `.claude/rules/`.
- **Security vulnerabilities** — anything `@rules/security/backend.md`, `@rules/security/frontend.md`, or `@rules/security/mobile.md` would flag (injection, missing authn/authz, unsafe deserialization, sensitive-data exposure, …).

Rules:

1. **Do not silently ignore** a pre-existing issue you encountered in a file you had to read for the CR fixes — fix it in this PR.
2. **Do not expand scope** by actively scanning unrelated files for additional pre-existing issues. Limit attention to files already touched by the CR fixes.
3. Land each pre-existing fix in its **own separate commit**, ordered **before** the CR-fix commits:
   - Use a Conventional Commits subject per `@rules/git/general.mdc`: `fix(<scope>): pre-existing — <description>` for bugs and security, `refactor(<scope>): pre-existing — <description>` for rule violations without behavior change.
   - The `pre-existing — ` prefix is mandatory so reviewers can identify these commits at a glance.
   - **Test coverage workflow depends on the commit type:**
     - `fix(<scope>): pre-existing — …` (bug, security) — add the regression test in the **same commit** as the fix; the test must fail before the fix lands and pass after.
     - `refactor(<scope>): pre-existing — …` (project-rule violation, behavior-preserving) — apply `@rules/refactoring/general.mdc` *Test Coverage Contract*: when the target lines are below 100% coverage, author a dedicated `test(<scope>): cover <area> before pre-existing refactor` commit **before** the refactor commit, and do **not** modify pre-existing tests inside the refactor commit (mechanical renames forced by the refactor itself stay exempt and must be flagged in the commit body).
   - Either way, pre-existing fixes follow the same diff-scoped 100% coverage rule as CR fixes.
4. In the `cr-status` PR comment posted during **PR update**, list every pre-existing fix under a `## Pre-existing fixes` heading with a one-line rationale, so reviewers can review them independently of the CR thread.
5. If a pre-existing issue is **non-trivial** (would significantly expand the PR or requires architectural discussion), do **not** fix it. Surface it in the `cr-status` comment as a deferred follow-up with the reason — the reviewer can then file a follow-up issue.

---

### Apply fixes

- Apply only requested review changes
- Keep scope strictly limited to review feedback
- Ensure DRY violations are included and resolved
- All production code changes must follow:
  - @skills/class-refactoring/SKILL.md

---

### Testing

- If tests are required or missing:
  - Run @skills/create-missing-tests-in-pr/SKILL.md
- Ensure current changes have 100% coverage **for the changed files only**, using the project's available coverage tooling (per the Coverage gate in `@skills/code-review/SKILL.md`). Do not gate on the full-suite coverage percentage during a CR / review loop iteration.
- Run only relevant tests for changed files
- If migrations were added, run `php artisan migrate`

---

### Review loop (mandatory — convergence gate)

This is a **blocking loop**. Do not advance to **Finalization**, **PR update**, or **Completion** until the loop converges. The final report (technical and non-technical) is published only **once**, after convergence.

1. Initialise `iteration = 1` and `maxIterations = 5` (safety net to avoid runaway loops).
2. **Run the review inline.** Invoke the appropriate CR wrapper directly in this skill's context — do not dispatch as a subagent. Each iteration re-invokes the CR wrapper inline so it reloads the diff after the latest fix commit:
   - GitHub: `@skills/code-review-github/SKILL.md`
   - JIRA: `@skills/code-review-jira/SKILL.md`
   The invocation **must** include the explicit quiet-mode instruction (see **Quiet review runs** below). The review run **must not** publish to the PR or to the issue tracker during loop iterations — capture findings in memory only.
3. Count `criticalCount` and `moderateCount` in the latest review.
4. If `criticalCount + moderateCount == 0` → **converged**, exit the loop.
5. Otherwise, apply the **Suggested Fix** snippet from each Critical / Moderate finding using the **Reproducer extraction** workflow above, run pre-push quality gates on touched files, increment `iteration`, and go back to step 2.
6. If `iteration > maxIterations` and the loop still has not converged, **stop and surface the remaining findings** to the user — do not push or publish a partial report. The user must triage the residual findings manually before any final report goes out.

#### Quiet review runs (during the loop)

- During iterations 1…N–1 of the loop, invoke the review skill with the explicit instruction "do not publish; return findings as in-memory markdown for this loop iteration only". Both `code-review-github` and `code-review-jira` honour the suppression: no PR comment, no JIRA comment, no linked-issue summary is posted while the loop is still iterating.
- The very last iteration (the one that observes `criticalCount + moderateCount == 0`) is the **only** iteration whose output is published — that publication is performed by the **PR update** + **Completion** steps below, not by the review skill itself.
- Loop iterations may write quality-gate output (composer scripts, build logs) to the local terminal — that is not "publishing" and is allowed.

---

### Pre-push quality gates

- Discover available fixers and checkers (prefer Phing targets from `build.xml`/`phing.xml`; fall back to Composer scripts in `composer.json`)
- Run available fixers on all changed files and fix any violations
- Run available checkers/analyzers on all changed files and resolve all reported errors

### Finalization (only after Review loop converged)

**Precondition:** the Review loop above must have exited with `criticalCount + moderateCount == 0`. If the loop hit `maxIterations` without converging, do not proceed — return the remaining findings to the user for manual triage instead.

- Do **not** auto-invoke `@skills/test-like-human/SKILL.md`. The user-perspective testing skill runs **on demand only** — leave it for the user to trigger via `/test-like-human` after the PR is updated.
- Commit and push changes
- If PR does not exist, create it according to @rules/git/general.mdc
  - Title in English (per `@rules/git/general.mdc`)
  - Body in the assignment language (per `@rules/reports/general.mdc`)

---

### PR update (only after Review loop converged)

**Precondition:** same as Finalization — convergence required.

- Publish the resolved-items report through the publish helper using the dedicated `cr-status` marker namespace, so the status comment is identifiable as a status post (separate from the CR comment in the `cr-comment` namespace). Concretely:
  - GitHub PR: `skills/code-review-github/scripts/upsert-comment.sh <PR-NUMBER|URL> - cr-status` (body on stdin). The helper appends `<!-- cr-status:actor=<gh-login> -->` to the body for traceability and **POSTs a new comment on every run** — it never PATCHes a prior status comment. Action (`created`) is logged on stderr; include it in the in-conversation completion report.
  - JIRA-originated reviews that also mirror to a JIRA ticket: `skills/code-review-jira/scripts/upsert-comment.sh <KEY|URL> - cr-status`. The helper appends `{anchor:cr-status-actor-<slug>}` and edits / adds the comment via `acli` (JIRA MCP fallback on exit code 4) — JIRA-side upsert behavior is unchanged.
- Do **not** quote / reply to a previous CR or status comment — the always-new-comment convention (GitHub) replaces the previous quoting / in-place edit flow entirely, and every converge run adds its own self-contained status comment so the chronological sequence is the audit trail. The CR comment (`cr-comment` namespace) stays untouched by this skill.
- Mark resolved items (checkbox or inline) inside the freshly posted body in all cases.
- When **Pre-fix phase** produced at least one pre-existing fix commit, render a dedicated `## Pre-existing fixes` section in the `cr-status` body listing each commit subject (`fix/refactor(<scope>): pre-existing — …`) with a one-line rationale derived from the commit body, so reviewers can review the pre-existing fixes independently of the CR thread. Omit the section entirely when no pre-existing fix landed (consistent with the always-omit-empty-section convention).

#### Resolve addressed reviewer threads (GitHub)

After the fixes are committed and pushed (Finalization above), mark every reviewer review thread whose finding was **actually fixed** as resolved, using the thread `id` captured during intake:

```
gh api graphql -f query='mutation($threadId:ID!){ resolveReviewThread(input:{threadId:$threadId}){ thread{ isResolved } } }' -F threadId=<thread-id>
```

- Resolve **only** threads that were fixed. Leave a thread unresolved when its point was rejected or deferred, and record the rejection reason in the `cr-status` report instead of resolving it.
- If `gh api graphql` is unavailable, fall back to the GitHub MCP server's resolve-review-thread operation.
- Resolving a thread is a GitHub PR state change, not a code change — it stays within the read-fixes-push-resolve flow this skill already owns and never touches the protected main branch.

#### Per-item justification (required)

Every resolved review point in the PR comment **must** include a brief justification using this format:

```
- [x] {short finding title}
  - **Why:** {what was wrong / what the reviewer asked for}
  - **Reason:** {root cause or rule that was violated}
  - **Solution:** {what was changed and why this is the best fit}
```

Rules:
- Keep each line **one sentence max**.
- Skip the section only if a point was rejected or deferred — in that case state the rejection reason instead.
- Do not pad with filler, restate the obvious, or paraphrase the diff.

---

### Completion (final, single publish)

**Precondition:** Review loop has converged (`criticalCount + moderateCount == 0`).

- **Run the final publishing run inline.** Invoke the appropriate CR wrapper directly in this skill's context with publishing enabled — this is the **only** review whose output reaches the PR / issue tracker. The invocation must include the PR URL, the converged state (Critical + Moderate == 0), and the instruction to post the final PR comment + linked-issue / JIRA mirror per the CR wrapper's contract. Do not dispatch as a subagent — run it sequentially in the current context:
  - GitHub: `@skills/code-review-github/SKILL.md`
  - JIRA: `@skills/code-review-jira/SKILL.md`
- Share a concise completion report (in-conversation, not on the tracker):
  - PR link
  - resolved items
  - reviewer threads resolved (count) and any left unresolved with the rejection / deferral reason
  - loop iteration count and final convergence status
  - remaining blockers (if any — should be empty when convergence was reached)

---

## Principles

- Resolve review feedback, do not expand scope
- Prefer minimal changes over unnecessary refactoring
- Do not introduce new bugs while fixing existing ones
- Keep changes traceable to review comments
- Ensure every review comment is explicitly addressed
- Treat unresolved GitHub reviewer threads as first-class checklist items; skip already-resolved threads, and resolve a thread only after its fix lands
- Avoid unnecessary commits or noise
