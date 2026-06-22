---
name: code-review-bugsnag
description: Use when run code review for a Bugsnag error and publish results to
  the linked GitHub PR and the Bugsnag error
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Code Review (Bugsnag)

## Purpose
Perform code review for a fix linked to a Bugsnag error by analyzing the related pull request and publishing results to:
- GitHub (technical findings, on the linked PR)
- Bugsnag (human-readable summary, as a comment on the error)

---

## Constraints
- Apply @rules/git/general.mdc
- Apply @rules/reports/general.mdc. The **GitHub PR technical comment** this skill posts (Status / Counts / Findings / Refactoring / Database Analysis / Coverage / Summary) stays in canonical English per the rule's *Exception — technical CR findings on the GitHub PR*. The **Bugsnag error comment** delegated to `@skills/pr-summary/SKILL.md` and the **mirrored linked-GitHub-issue summary** follow the language of the source assignment. Never mix languages inside the same comment.
- **Read-only skill** — never modify code, never stage / commit / push changes, and never run any git write operation (`git add`, `git commit`, `git push`, `git reset`, `git checkout -- …`, etc.). Checking out the relevant branch and `git pull` to read the latest code are **required** (the mandatory Branch checkout gate below); mutating the working tree or pushing to the remote is not. Publishing is limited to PR / linked-issue comments via `gh` and to the Bugsnag error comment via `skills/code-review-bugsnag/scripts/upsert-comment.sh`.
- Bugsnag output must be understandable for non-developers
- Output findings only (no praise)

---

## Execution

### 1. Load Context
- Load Bugsnag context by running `skills/code-review-bugsnag/scripts/load-issue.sh <URL|TRIPLE>` — the single deterministic entry point. Requires `BUGSNAG_TOKEN` (a Data Access API token). Never call `api.bugsnag.com` directly. Read the error class, `message`, `context`, `status`, `severity`, `latestEvent.stacktrace` (the in-project frames are the reproduction entry point), `comments[]`, and `linkedIssues[]` off the resulting JSON document.
- For a single ready-to-read context brief — the error header, latest event (app version, failing request, in-project stacktrace frames), comments, linked issues, and an inventory of external URLs, rendered as Markdown — run `skills/code-review-bugsnag/scripts/gather-issue-context.sh <URL|TRIPLE>` instead of hand-assembling the JSON. To read only the comments as a structured array, use `skills/code-review-bugsnag/scripts/parse-comments.sh <URL|TRIPLE>`. Both build on `load-issue.sh`, so the same exit codes, `BUGSNAG_TOKEN` requirement, and MCP fallback apply. `linkedIssues` point at GitHub — load that linked issue with `skills/code-review-github/scripts/gather-issue-context.sh <URL>` when you need its full context.
- The script accepts an `app.bugsnag.com/<org>/<project>/errors/<id>` URL or an `<org>/<project>/<error-id>` triple.
- If the script is unavailable (missing tool/token, exit code 2/3) fall back to a Bugsnag MCP server.
- Identify the linked PR to review from the error's `linkedIssues[]` (the mirrored GitHub issue/PR). Load that PR with `skills/code-review-github/scripts/load-issue.sh <URL>` to get the diff, `commits[]`, and `closingIssues[]`.
- **Branch checkout gate (mandatory, always).** Before running any review step, check out the PR branch (`headRefName` from the loaded PR JSON) and pull the latest commits — `git fetch origin`, `git checkout <headRefName>`, `git pull` — so the review always runs against the **actual current codebase on disk (the checked-out working tree)**, never against the remote diff in isolation. Confirm local `HEAD` equals the PR head SHA. If the checkout fails (missing ref, detached `HEAD`, or local changes that would be overwritten), **stop and report it** instead of reviewing from the diff. Every sub-review then reads the checked-out files.

#### Issue Context Analysis
Before reviewing code, treat the Bugsnag error as the assignment:

1. The error class + `message` + `context` describe the failure; the in-project `latestEvent.stacktrace` frames pinpoint the code path that must be fixed.
2. Extract the expected behavior: the error must no longer occur for the reproduced scenario, and a regression test must capture the failure.
3. Read every entry in `comments[]` for human-authored context (e.g. "Fixed in db", reproduction notes), plus any acceptance criteria on the linked GitHub issue.
4. Verify the fix is covered by a regression test that fails before and passes after. Flag missing coverage as a finding.

### 2. Pre-checks
- **CI coverage of checks.** Read `statusCheckRollup[]` from the GitHub PR JSON loaded in step 1 (via `skills/code-review-github/scripts/load-issue.sh`). Identify which checks ran on the PR head commit (`headRefOid`) and their result. Pass this CI check map to the Coverage gate decision in `@skills/code-review/SKILL.md` (Validation → Coverage gate → Reuse CI results) so only missing or non-green checks are run locally.
- If the linked PR has conflicts → skip review for that PR.
- If the error has no linked PR yet → report "no linked PR — review skipped" and stop.

### 3. Run Reviews

> **Inline dispatch.** Each sub-review runs **inline in this wrapper's context** — invoke each skill directly (`@skills/<name>/SKILL.md` with any `MODE=cr` flag), passing the PR URL / number and the branch already checked out, and declare the publishing contract for this CR run (quiet vs publish; see step 4). Run the sub-reviews **one at a time** — do not dispatch them as parallel subagents.

- For the linked PR (sub-reviews invoked inline, one at a time):
  - run @skills/assignment-compliance-check/SKILL.md — Bugsnag-originated branch: the error (class / message / context / stacktrace) is the assignment, plus any acceptance criteria on the linked GitHub issue. The skill **does not publish anywhere itself** — it returns the assembled `## Assignment Compliance` markdown block (only when at least one Critical gap exists) or a skip status. The wrapper passes a returned block to `@skills/pr-summary/SKILL.md` as an embedded block so each tracker receives **one consolidated comment** per CR run (per issue #498). **Do not embed** the block into the GitHub PR comment.
  - run @skills/code-review/SKILL.md
  - run @skills/analyze-problem/SKILL.md — **always run, scoped to assignment conformance**, invoked inline and read-only (analysis-only — no plan artifact, no code / git writes). Here the assignment is the Bugsnag error (class / message / context / stacktrace) plus any acceptance criteria on the linked GitHub issue; the skill compares that assignment against what the PR diff actually implements and raises every unmet requirement as a **Critical** finding with reproducer fields on the GitHub PR comment. Canonical definition lives in `@skills/code-review/SKILL.md` Specialized Reviews → Always run; it is distinct from the per-Critical-finding verification (issue #537) and must not duplicate gaps already raised by `assignment-compliance-check`.
  - run @skills/security-review/SKILL.md
  - run @skills/class-refactoring/SKILL.md **with `MODE=cr`** — read-only refactoring lens scoped to the PR diff.

- Run conditionally (same triggers as `@skills/code-review-jira/SKILL.md`): refactoring diff → `@skills/refactor-entry-point-to-action/SKILL.md` with `MODE=cr`; database operations → `@skills/mysql-problem-solver/SKILL.md` (surface under `## Database Analysis`); shared state → `@skills/race-condition-review/SKILL.md`; third-party API changes → the Third-Party API & Service Analysis step from `@skills/code-review/SKILL.md`.

### 4. Publish Results

> **Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`):** when the caller requests "do not publish; return findings as in-memory markdown", **skip all publishing** below and return the assembled review markdown. Only the final (publishing) call after convergence runs Publish Results in full.

#### GitHub (technical findings only — always-new comment per CR run)
- Publish via `skills/code-review-github/scripts/upsert-comment.sh <PR-NUMBER|URL> -` (body on stdin) on the linked PR. Every CR run posts a **fresh PR comment**; the helper appends the marker `<!-- cr-comment:actor=<gh-login> -->` for traceability and never edits a prior comment in place. On exit code 2/3, fall back to the GitHub MCP server's `addIssueComment` as a fresh post.
- Format inside the comment body: Critical → Moderate → Minor → Refactoring (DRY / Tech Debt Reduction), each with `file:line` and an actionable fix. Use the template defined in `templates/github-output.md`. Omit empty sections entirely per `@skills/code-review/SKILL.md` Output Rules.
- This is the only place where technical details appear.

#### Bugsnag (consolidated non-technical comment)
- Delegate the Bugsnag error comment to `@skills/pr-summary/SKILL.md`. This CR skill must not author its own summary — the goal is the uniform *"Authors / Available behind / Summary of changes / How to test"* output that non-developers understand.
- **Consolidation contract (issue #498):** invoke `pr-summary` exactly once for the Bugsnag error. When `@skills/assignment-compliance-check/SKILL.md` returned a markdown block, pass it as an embedded block so the Bugsnag audience sees **one consolidated comment**. `pr-summary` posts the comment via `skills/code-review-bugsnag/scripts/upsert-comment.sh <URL|TRIPLE> -` (Bugsnag MCP server fallback on exit code 2/3). Each CR run posts a fresh comment (Bugsnag renders plain text — no hidden per-actor marker; the token identifies the author).
- Pass through the PR `author.login` + `commits[].author.login` set and the git `%an <%ae>` log so the published comment credits the **real change author(s)**, never the agent / CR identity. Also pass through any **test-parameter gating** detected in the diff so the comment carries the `Available behind` line.
- Never post file paths, line numbers, code snippets, technical severity levels, or finding counts to Bugsnag — `pr-summary` enforces this by design.

#### Linked GitHub issues (consolidated mirror — always-new comment per CR run)
- If the linked PR also references a GitHub issue (`closingIssues[]` non-empty), delegate the linked-GitHub-issue comment to `@skills/pr-summary/SKILL.md` (GitHub tracker target) and post via `skills/code-review-github/scripts/upsert-comment.sh` on each entry — one fresh comment per linked issue per CR run. Pass the same author + test-parameter context. If `closingIssues[]` is empty, note "no linked GitHub issue — mirror skipped" in the PR comment summary line.

---

## Output Rules

### GitHub (technical report — only here)
- All technical findings go exclusively to the linked GitHub PR comment: file paths, line numbers, code references, severity levels (Critical / Moderate / Minor), concrete fixes. Findings only — no praise.
- Each Critical / Moderate finding carries the four reproducer fields (**Faulty Example**, **Expected Behavior**, **Test Hint**, **Suggested Fix**) so `@skills/process-code-review/SKILL.md` can convert each finding into a reproducer test and apply the fix. Omit empty sections and the coverage surfaces per `@skills/code-review/SKILL.md` Output Rules.

### Bugsnag (non-technical summary — only here)
- The non-technical Bugsnag comment is **produced and posted by `@skills/pr-summary/SKILL.md`**, not by this skill. Plain language understandable by non-developers, in two sections: *Summary of changes* and *How to test*. No file paths, line numbers, code snippets, or severity jargon.

---

## Principles

- Focus on risks, not style
- Prefer impact over quantity
- Avoid duplication of findings
- Prioritize regression detection
- Be precise and actionable

---

## After Completion

- Do **not** auto-invoke `@skills/test-like-human/SKILL.md`. It runs **on demand only**; CR-track skills must never chain into it.
- Do **not** change the Bugsnag error status (fixed / ignored / snoozed) automatically — marking an error fixed is left to a human after the fix is verified in production.
