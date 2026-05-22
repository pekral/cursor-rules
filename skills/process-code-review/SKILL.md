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
- Before processing a PR, switch to the PR branch and pull latest changes

### For each PR:

- Load PR context by running `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — the single deterministic entry point. Never call `gh issue view`, `gh pr view`, or `gh api /repos/.../issues/...` directly. Read review comments, files, commits, status checks, and `closingIssues` off the resulting JSON document. If the script is unavailable (missing tool, exit code 2/3) fall back to the GitHub MCP server, and always prefer the MCP fallback for review-thread / line-anchored comments that the script does not return.
- Build a checklist from all review findings (general comments come from `comments[]`; line-anchored review-thread comments still need the MCP fallback)
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

If a finding lacks Faulty Example, Expected Behavior, or Test Hint, request a CR rerun rather than guessing — the CR skills are responsible for providing them. Suggested Fix may legitimately be `n/a` per the CR rules.

---

### Pre-fix phase

- Scan affected files for pre-existing bugs
- Fix them in a **separate commit** before applying review fixes

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
- Ensure current changes have 100% coverage by running the **diff-scoped** coverage command only (discovery order per `@skills/code-review/SKILL.md` Coverage gate — `vendor/bin/test-coverage-diff` from this package, Phing `test:coverage:diff` / `coverage:diff`, Composer `test:coverage:diff`, or any project-specific `*coverage*diff*` script). Never run the full-suite coverage command during a CR / review loop iteration.
- Run only relevant tests for changed files
- If migrations were added, run `php artisan migrate`

---

### Review loop (mandatory — convergence gate)

This is a **blocking loop**. Do not advance to **Finalization**, **PR update**, or **Completion** until the loop converges. The final report (technical and non-technical) is published only **once**, after convergence.

1. Initialise `iteration = 1` and `maxIterations = 5` (safety net to avoid runaway loops).
2. Run the appropriate review skill:
   - GitHub: `@skills/code-review-github/SKILL.md`
   - JIRA: `@skills/code-review-jira/SKILL.md`
   The review run **must not** publish to the PR or to the issue tracker — capture findings in memory only. (See **Quiet review runs** below for how to suppress publishing.)
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

- Publish the resolved-items report through the **single-comment upsert helper** using the dedicated `cr-status` marker namespace, so the status comment lives in its own per-(PR, actor) slot — separate from the CR comment (`cr-comment` namespace) — and follow-up converge runs edit it in place instead of stacking on top of it. Concretely:
  - GitHub PR: `skills/code-review-github/scripts/upsert-comment.sh <PR-NUMBER|URL> - cr-status` (body on stdin). The helper appends `<!-- cr-status:actor=<gh-login> -->` to the body, locates any prior `cr-status`-namespaced comment by the same actor, and either PATCHes it or POSTs a fresh one. Action (`created|updated`) is logged on stderr; include it in the in-conversation completion report.
  - JIRA-originated reviews that also mirror to a JIRA ticket: `skills/code-review-jira/scripts/upsert-comment.sh <KEY|URL> - cr-status`. The helper appends `{anchor:cr-status-actor-<slug>}` and edits / adds the comment via `acli` (JIRA MCP fallback on exit code 4).
- Do **not** quote / reply to the CR comment and do **not** open a new top-level PR comment outside the upsert flow — the upsert convention replaces the previous quoting-based visual thread entirely. The CR comment (`cr-comment` namespace) stays untouched by this skill; only the actor-owned `cr-status` comment is edited.
- Mark resolved items (checkbox or inline) inside the upserted body in all cases.

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

- Trigger the final review run **with publishing enabled** — this is the **only** review whose output reaches the PR / issue tracker:
  - GitHub: `@skills/code-review-github/SKILL.md`
  - JIRA: `@skills/code-review-jira/SKILL.md`
- Share a concise completion report (in-conversation, not on the tracker):
  - PR link
  - resolved items
  - loop iteration count and final convergence status
  - remaining blockers (if any — should be empty when convergence was reached)

---

## Principles

- Resolve review feedback, do not expand scope
- Prefer minimal changes over unnecessary refactoring
- Do not introduce new bugs while fixing existing ones
- Keep changes traceable to review comments
- Ensure every review comment is explicitly addressed
- Avoid unnecessary commits or noise
