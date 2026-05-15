---
name: code-review-github
description: Use when perform code review for GitHub pull requests and post
  findings as PR comments plus a non-technical summary to every linked issue
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Code Review (GitHub)

## Purpose
Run a full code review for GitHub pull requests and publish findings directly to the PR.

---

## Constraints
- Apply @rules/git/general.mdc
- All output posted to GitHub must be in English
- Never modify code
- Output findings only (no praise)

---

## Execution

### 1. Load Context
- Load PR context by running `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — the single deterministic entry point. Never call `gh issue view`, `gh pr view`, or `gh api /repos/.../issues/...` directly. Read PR header, description, comments, commits, files, reviews, status checks, and `closingIssues` off the resulting JSON document.
- Load each linked issue (from `closingIssues[]`) the same way — pass its number or URL to the same script.
- If the script is unavailable (missing tool, exit code 2/3) fall back to the GitHub MCP server. Always prefer the MCP fallback for data the script cannot cover: review-thread / line-anchored comments, per-commit check runs, and binary attachment contents.
- If multiple PRs exist for one issue, review each independently
- Before reviewing a PR, switch to the PR branch and pull latest changes

#### Issue Context Analysis
Before reviewing code, load and analyze the full linked issue:

1. Fetch the complete GitHub issue via `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — description, all comments, and any referenced attachments or links come off the resulting JSON document.
2. Extract from the issue:
   - **Requirements and acceptance criteria** — what the code must do
   - **Expected behavior** — how the feature or fix should work
   - **Edge cases and constraints** — mentioned by the reporter or in comments
   - **Test data** — any sample inputs, payloads, or scenarios provided in the issue
3. Use this context to evaluate whether the implementation fully satisfies the issue — not just whether the code is technically correct.
4. If the issue contains test data or test scenarios, verify they are covered by existing or new tests. Flag missing test coverage as a finding.

### 2. Pre-checks
- If PR has merge conflicts → cancel review

### 3. Run Reviews

- Always run:
    - @skills/assignment-compliance-check/SKILL.md — non-technical business-logic vs assignment check. The skill publishes the **Assignment Compliance** report as a dedicated comment on every linked GitHub issue listed in `closingIssues[]` and returns only a status string (`posted to issue #N`, `no linked issue — assignment compliance skipped`, or `failed to publish …`). **Do not embed** the report into the PR comment — keep the PR comment focused on technical findings and surface the returned status in the summary line.
    - @skills/code-review/SKILL.md
    - @skills/security-review/SKILL.md
    - @skills/class-refactoring/SKILL.md — read-only refactoring lens scoped to the PR diff. Surface DRY duplication and tech-debt-reducing changes that apply to lines actually touched by the PR. Do not propose changes outside the diff.

- Run conditionally:
    - Database changes → @skills/mysql-problem-solver/SKILL.md — when reviewing new or modified SQL / Eloquent / query-builder queries, prefer rewriting the query to reuse an existing schema index over proposing a new one (see `@rules/sql/optimalize.mdc` "Reuse existing indexes first")
    - Shared state → @skills/race-condition-review/SKILL.md
    - Third-party API or service changes → ensure the **Third-Party API & Service Analysis** step from `@skills/code-review/SKILL.md` is executed for the diff

#### Refactoring & Tech Debt (DRY) Analysis (PR diff only)

1. Restrict the analysis to lines added or modified in the PR — never review untouched code.
2. For each changed block, apply `@skills/class-refactoring/SKILL.md` and look for:
   - duplicated logic that already exists elsewhere (DRY) — verify the change reuses existing logic instead of introducing a parallel implementation, per `@rules/code-review/general.mdc` Reuse Existing Logic section
   - data shaping repeated across Actions/Services/controllers/jobs/listeners/Livewire/commands
   - oversized methods, deep nesting, mixed responsibilities introduced or amplified by the change
3. Each finding must include the file path, the affected line range, and a concrete refactoring that *reduces* tech debt.
4. In-scope refactorings go into the **Refactoring (DRY / Tech Debt Reduction)** section of the PR comment template. Out-of-scope structural problems still belong in **Refactoring Proposals**.

### 4. Post Results

> **Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`):** when the caller explicitly requests "do not publish; return findings as in-memory markdown for this loop iteration only", **skip the entire Post Results step** — do not post the PR comment, do not post the linked-issue summary. Return the assembled review markdown to the caller and stop. Only the very last (publishing) call from `process-code-review` after convergence runs Post Results in full.

#### Thread detection
- Before posting, search for an existing code review comment on the PR:
  - Read `comments` off the JSON loaded in step 1 and find one matching the CR format (e.g. contains "Summary:" with severity counts)
  - Store its `url` (and the trailing `#issuecomment-<id>`) if found

#### Posting strategy
- **If an existing CR comment is found (follow-up review):**
    - Analyze all previous CR findings and classify each as: **Resolved**, **Deferred**, or **Still open**
    - Include a **Previous CR Status** section at the top of the new review comment (before any new findings)
    - Post **detailed findings** as a new PR comment that references the original CR comment (quote its first line or link to it)
    - GitHub does not support native replies to issue comments — use quoting (e.g. "> Replying to code review from {date}") to create a visual thread

- **If no existing CR comment is found (first review):**
    - Post findings as a single PR comment using CLI tools

#### Format
- Critical → Moderate → Minor → Refactoring (DRY / Tech Debt Reduction)
- Include file + line in the finding body
- Include actionable fix
- Post all findings inside the single PR comment — never as line-anchored review comments.

- If no findings:
    - post: "No findings identified"

#### Issue tracker summary (mandatory)
- After posting the PR comment, delegate the **non-technical summary on every linked issue** listed in `closingIssues[]` of the JSON loaded in step 1 to `@skills/pr-summary/SKILL.md`. This CR skill must not author its own non-technical template — the goal is a uniform *"Summary of changes + How to test"* output across both trackers that non-technical project managers understand and can act on.
- Invoke `@skills/pr-summary/SKILL.md` with the **GitHub** tracker target so it renders `@skills/pr-summary/templates/pr-summary-github.md` in GitHub Markdown and posts via `gh issue comment <number>` on every entry in `closingIssues[]`. `pr-summary` mirrors the same format that `@skills/code-review-jira/SKILL.md` posts to JIRA, so reviewers reading either tracker see the same summary.
- `pr-summary` enforces the no-file-paths / no-line-numbers / no-code-snippets / no-severity-jargon contract by design; technical content stays exclusively on the PR comment.
- If `closingIssues[]` is empty, skip this step and note "no linked issue — issue summary skipped" in the PR comment summary line.
- If `gh issue comment` returns a permission error (cross-repo issue, lacking write access), log the failure in the PR comment summary line and continue — do not abort the review.
- For follow-up reviews, post a fresh summary comment on each linked issue rather than editing prior comments (matches the PR thread-detection behavior).

---

## Output Rules

- Findings only
- No praise
- No “what was checked”
- Use exactly three severity levels: Critical, Moderate, Minor
- Add a **Refactoring (DRY / Tech Debt Reduction)** section after the Minor findings whenever the diff contains in-scope tech-debt-reducing changes (DRY duplication, oversized methods, mixed responsibilities). Each item must include `file:line` and a concrete refactoring step.
- Each **Critical** and **Moderate** finding must include:
    - **Faulty Example** — minimal code snippet or input payload reproducing the issue (redact secrets/PII)
    - **Expected Behavior** — single assertable statement (return value, exception, persisted state, emitted event)
    - **Test Hint** — one sentence pointing at the test layer (unit, integration, feature) and entry point
    - **Suggested Fix** — minimal corrected code snippet that resolves the finding. Must comply with `@rules/php/core-standards.mdc` and, for Laravel projects, `@rules/laravel/architecture.mdc`. Use `n/a — <reason>` only when a snippet adds no value over the one-line Fix description (e.g. naming-only changes, dead-code removal, pointers to an existing helper whose name already says enough).
- These four fields exist so `@skills/process-code-review/SKILL.md` can convert each finding into a reproducer test and apply the fix directly from the PR comment.
- Minor findings may omit these fields when no behavior change is implied.
- If reviewed code violates project rules or architecture but is **out of scope** for the current PR, add a **Refactoring Proposals** section with issue drafts (justified by defined rules only)
- The posted PR comment must always include a `## Coverage` section before the summary line. The section reports the tool used, command run, and coverage result for changed lines (or "tooling unavailable" with reason). Never post a CR comment without it.
- The PR comment summary line must report the issue-tracker summary status — `posted summary to issue #N` (or comma-separated list when multiple), `no linked issue — issue summary skipped`, or `failed to post on issue #N: <reason>` when a permission / network error occurs. Never post a CR comment without it.
- End with summary line

## Output Format

Use the template defined in `templates/pr-comment-output.md`.
