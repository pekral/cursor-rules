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
- Apply @rules/reports/general.mdc. The **technical CR PR comment** this skill posts on the GitHub PR (Status / Counts / Findings / Refactoring / Coverage / Summary) stays in canonical English per the rule's *Exception — technical CR findings on the GitHub PR*. The **non-technical mirror** delegated to `@skills/pr-summary/SKILL.md` for every `closingIssues[]` linked GitHub issue follows the language of the source assignment. Never mix languages inside the same comment; never use bilingual *Kritické (Critical)* style parentheses.
- **Read-only skill** — never modify code, never stage / commit / push changes, and never run any git write operation (`git add`, `git commit`, `git push`, `git reset`, `git checkout -- …`, etc.). Checking out the relevant branch and `git pull` to read the latest code are **required** (the mandatory Branch checkout gate below); mutating the working tree or pushing to the remote is not. Publishing is limited to PR / linked-issue comments via `gh`.
- Output findings only (no praise)

---

## Execution

### 1. Load Context
- Load PR context by running `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — the single deterministic entry point. Never call `gh issue view`, `gh pr view`, or `gh api /repos/.../issues/...` directly. Read PR header, description, comments, commits, files, reviews, status checks, and `closingIssues` off the resulting JSON document.
- For a single ready-to-read context brief — the issue/PR plus its body, comments, changed files, commits, reviews, CI checks, recursively-loaded linked issues/PRs, and an inventory of external URLs, rendered as Markdown — run `skills/code-review-github/scripts/gather-issue-context.sh <NUMBER|URL>` instead of hand-assembling the JSON. To read only the comments as a structured array, use `skills/code-review-github/scripts/parse-comments.sh <NUMBER|URL>`. Both build on `load-issue.sh`, so the same exit codes and MCP fallback apply. Attachment content and the inventoried URLs are not fetched by the scripts — read them with your own tools when a finding depends on them.
- Load each linked issue (from `closingIssues[]`) the same way — pass its number or URL to the same script.
- If the script is unavailable (missing tool, exit code 2/3) fall back to the GitHub MCP server. Always prefer the MCP fallback for data the script cannot cover: review-thread / line-anchored comments, per-commit check runs, and binary attachment contents.
- If multiple PRs exist for one issue, review each independently
- **Branch checkout gate (mandatory, always).** Before running any review step, check out the PR branch (`headRefName` from the loaded JSON) and pull the latest commits — `git fetch origin`, `git checkout <headRefName>`, `git pull` — so the review always runs against the **actual current codebase on disk (the checked-out working tree)**, never against the `gh` remote diff in isolation. Confirm local `HEAD` equals the PR head SHA from the loaded context. If the checkout fails (missing ref, detached `HEAD`, or local changes that would be overwritten), **stop and report it** instead of reviewing from the diff. Every sub-review then reads the checked-out files.

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

> **Inline dispatch.** Each sub-review below runs **inline in this wrapper's context** — invoke each skill directly (`@skills/<name>/SKILL.md` with any `MODE=cr` flag), passing the PR URL / number and the branch already checked out, and declare the publishing contract for this CR run (quiet vs publish; see step 4). Each invoked skill must return its findings as the canonical markdown block (`## Assignment Compliance` block, Critical / Moderate / Minor lists with reproducer fields, refactoring proposals). The CR wrapper then assembles the outputs into the final PR comment + linked-issue summary. Run the sub-reviews **one at a time** — do not dispatch them as parallel subagents.
>
> The mysql-problem-solver / race-condition-review / refactor-entry-point-to-action conditionals follow the same rule: when their trigger fires, invoke them inline after the always-run set, still one at a time.

- Always run (inline, one at a time):
    - @skills/assignment-compliance-check/SKILL.md — non-technical business-logic vs assignment check. The skill **does not publish anywhere itself** — it returns either the assembled `## Assignment Compliance` markdown block (only when at least one Critical gap exists), the status `no critical gaps — assignment compliance block omitted` (when the implementation satisfies every stated requirement), or the status `no linked issue — assignment compliance skipped` (when `closingIssues[]` is empty). The CR wrapper passes the returned block as an embedded block to `@skills/pr-summary/SKILL.md` **only when a block is returned** so the linked-issue audience reads **one consolidated comment** per CR run (per issue #498) — on either skip status the wrapper embeds nothing and surfaces the status on the PR comment summary line. **Do not embed** the block into the PR comment — keep the PR comment focused on technical findings and surface the consolidated-comment status in the summary line.
    - @skills/code-review/SKILL.md
    - @skills/analyze-problem/SKILL.md — **always run, scoped to assignment conformance**, invoked inline and read-only (analysis-only — no plan artifact, no code / git writes). Compares the loaded issue requirements / acceptance criteria / expected behavior against what the PR diff actually implements and raises every unmet requirement as a **Critical** finding with reproducer fields. Canonical definition lives in `@skills/code-review/SKILL.md` Specialized Reviews → Always run; it is distinct from the per-Critical-finding verification (issue #537) and must not duplicate gaps already raised by `assignment-compliance-check`.
    - @skills/security-review/SKILL.md
    - @skills/class-refactoring/SKILL.md **with `MODE=cr`** — read-only refactoring lens scoped to the PR diff. Surface DRY duplication and tech-debt-reducing changes that apply to lines actually touched by the PR. `MODE=cr` guarantees no code changes, commits, fixers, or review chaining. Do not propose changes outside the diff.

- Run conditionally:
    - **Diff is a refactoring (behavior-preserving structural change per `@rules/refactoring/general.mdc`) → run the full refactoring skill set read-only.** When the PR restructures existing code without adding a feature or changing observable behavior, additionally invoke `@skills/refactor-entry-point-to-action/SKILL.md` **with `MODE=cr`** to surface the entry-point → Action proposals. **Both refactoring skills run read-only — no code changes, no commits, no fixers, no review chaining — `MODE=cr` enforces this.** Fold their output into the **Refactoring (DRY / Tech Debt Reduction)** section (in-scope) and **Refactoring Proposals** section (out-of-scope) of the PR comment.
    - **Database operations detected in the diff → `@skills/mysql-problem-solver/SKILL.md` is mandatory.** Trigger pattern list is owned by `@skills/code-review/SKILL.md` Specialized Reviews (raw SQL, Eloquent / query-builder calls, eager loads, model scopes, ModelManager / Repository methods, migrations, seeders, DynamoDB / NoSQL access). Capture its findings and surface them in the published PR comment under the dedicated `## Database Analysis` section (see Output Rules) — never silently fold them into the Critical / Moderate / Minor buckets.
    - Shared state → @skills/race-condition-review/SKILL.md
    - Third-party API or service changes → ensure the **Third-Party API & Service Analysis** step from `@skills/code-review/SKILL.md` is executed for the diff

#### Refactoring & Tech Debt (DRY) Analysis (PR diff only)

1. Restrict the analysis to lines added or modified in the PR — never review untouched code.
2. For each changed block, apply `@skills/class-refactoring/SKILL.md` (run with `MODE=cr` — read-only) and look for:
   - duplicated logic that already exists elsewhere (DRY) — verify the change reuses existing logic instead of introducing a parallel implementation, per `@rules/code-review/general.mdc` Reuse Existing Logic section
   - data shaping repeated across Actions/Services/controllers/jobs/listeners/Livewire/commands
   - oversized methods, deep nesting, mixed responsibilities introduced or amplified by the change
   - when the PR is itself a refactoring (see the conditional trigger in **Run Reviews**), also fold in the entry-point → Action proposals from `@skills/refactor-entry-point-to-action/SKILL.md` run with `MODE=cr`
3. Each finding must include the file path, the affected line range, and a concrete refactoring that *reduces* tech debt.
4. In-scope refactorings go into the **Refactoring (DRY / Tech Debt Reduction)** section of the PR comment template. Out-of-scope structural problems still belong in **Refactoring Proposals**.

### 4. Post Results

> **Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`):** when the caller explicitly requests "do not publish; return findings as in-memory markdown for this loop iteration only", **skip the entire Post Results step** — do not post the PR comment, do not post the linked-issue summary. Return the assembled review markdown to the caller and stop. Only the very last (publishing) call from `process-code-review` after convergence runs Post Results in full.

#### Always-new comment (per CR run)
- Every CR run posts a **fresh PR comment**. The helper never edits a prior comment in place — each run produces its own self-contained entry so reviewers see one comment per run, in chronological order. The hidden marker `<!-- cr-comment:actor=<gh-login> -->` is still appended to the body for traceability (auto-appended by the helper), but it no longer drives an upsert lookup.
- Publish via `skills/code-review-github/scripts/upsert-comment.sh <PR-NUMBER|URL> -` (body on stdin). The helper detects the current actor (`gh api user --jq .login`), appends the marker, and POSTs a new comment. The published URL is emitted on stdout; the action (`created`) on stderr — log it in the PR comment summary line.
- If the helper exits with code 2 (missing tool) or 3 (API failure), fall back to the GitHub MCP server's `addIssueComment` — also as a fresh post. Never quote / reply to an earlier CR comment and never call `updateIssueComment` to edit one in place; the always-new-comment convention replaces the previous in-place edit flow.

#### Format
- Critical → Moderate → Minor → Refactoring (DRY / Tech Debt Reduction)
- Include file + line in the finding body
- Include actionable fix
- Post all findings inside the single PR comment — never as line-anchored review comments.

- If no findings:
    - post the header block (Status / Counts / Last updated / Issue tracker summary) and the final `Summary` line only. The `Coverage:` header line, the `## Coverage` section, and the `coverage …` slot in the summary line are all dropped when every changed line is at 100% coverage and the tool ran successfully — only render them when the coverage gate produced uncovered changed lines (Critical findings) or unavailable / non-runnable coverage tooling (Critical finding). Omit every other section entirely. Do not append a "No findings identified" line — the Counts line `Critical 0 · Moderate 0 · Minor 0 · Refactoring 0` already signals the clean state and the omitted sections confirm there is nothing to fix.

#### Linked-issue consolidated summary (mandatory — single comment per linked issue)
- After posting the PR comment, delegate the **single consolidated summary on every linked issue** listed in `closingIssues[]` of the JSON loaded in step 1 to `@skills/pr-summary/SKILL.md`. This CR skill must not author its own non-technical template — the goal is a uniform *"Authors / Available behind / Summary of changes / How to test"* output across both trackers that non-technical project managers understand and can act on.
- **Consolidation contract (issue #498):** invoke `pr-summary` exactly once per linked issue. When `@skills/assignment-compliance-check/SKILL.md` returned a markdown block (i.e. at least one Critical gap was detected), pass that block as an embedded block — `pr-summary` appends it verbatim after `How to test` and publishes **one consolidated comment per CR run** containing both the change summary and the assignment-compliance verdict. When the compliance check returned the `no critical gaps — assignment compliance block omitted` status, **do not** pass an embedded block; `pr-summary` publishes the change summary alone (no "Verdict: clean" line, no "What is satisfied" list — clean compliance is reported by the absence of the block). The CR run posts **exactly one comment per linked issue per run** — never a separate `gh issue comment` for assignment compliance on top of it. Follow-up CR runs add new comments rather than editing prior ones, so the linked-issue thread keeps a chronological audit trail.
- When invoking `pr-summary`, pass through the PR `author.login` + `commits[].author.login` set and the git `%an <%ae>` log so the published summary credits the **real change author(s)**, never the agent or the identity running this CR. `pr-summary` resolves and prints those identities in its `Authors` line — confirm the line is present in the published comment.
- When invoking `pr-summary`, also pass through any **test-parameter gating** detected in the diff (feature flag, ENV switch, query-string parameter, request header, admin toggle, allow-list) so the published summary carries the `Available behind` line and folds the toggle-enabling step into `How to test` step 1. When the diff contains no such gate, confirm with `pr-summary` that the line is omitted intentionally rather than forgotten.
- Invoke `@skills/pr-summary/SKILL.md` with the **GitHub** tracker target so it renders `@skills/pr-summary/templates/pr-summary-github.md` in GitHub Markdown and posts the comment via `skills/code-review-github/scripts/upsert-comment.sh` on every entry in `closingIssues[]` (one fresh comment per linked issue per CR run — marker `<!-- cr-comment:actor=<gh-login> -->` is appended for traceability but no in-place edit is performed). `pr-summary` mirrors the same format that `@skills/code-review-jira/SKILL.md` posts to JIRA, so reviewers reading either tracker see the same consolidated comment.
- `pr-summary` enforces the no-file-paths / no-line-numbers / no-code-snippets / no-severity-jargon contract by design; technical content stays exclusively on the PR comment. The embedded `Assignment Compliance` block follows the same constraint — it carries plain-language gap descriptions only.
- If `closingIssues[]` is empty, skip this step and note "no linked issue — issue summary skipped" in the PR comment summary line. `assignment-compliance-check` returns the same status in that case so the wrapper does not even build an embedded block. When `closingIssues[]` is non-empty and `assignment-compliance-check` returned the `no critical gaps — assignment compliance block omitted` status, the wrapper still publishes the consolidated `pr-summary` comment on every linked issue, but without an embedded compliance block — clean compliance is reported by the absence of the block, consistent with every other "OK" section that is dropped rather than surfaced.
- If the upsert helper or the GitHub MCP fallback returns a permission error (cross-repo issue, lacking write access), log the failure in the PR comment summary line and continue — do not abort the review.
- For follow-up reviews, the helper **posts a new linked-issue comment** instead of editing the prior one. The "one consolidated comment per CR run" rule applies **per run** — each run adds a fresh comment so the linked-issue thread carries the full chronological history of CR outputs. Old comments authored before this convention was introduced are left in place untouched.

---

## Output Rules

- Findings only
- No praise
- No “what was checked”
- **Omit empty sections entirely.** Only the header block (Status / Counts / Last updated / Issue tracker summary) and the final `Summary` line are always rendered in the PR comment. The `Coverage:` header line, the `## Coverage` section, and the `coverage …` slot in the summary line are all conditional — render them **only** when the coverage gate produced something to report (uncovered changed lines or unavailable / non-runnable tooling, both Critical findings). When every changed line is at 100% coverage and the tool ran successfully, drop all three coverage surfaces; the Counts line is the clean signal. Every other section — `Findings` (including each severity sub-heading), `Refactoring (DRY / Tech Debt Reduction)`, `Refactoring Proposals`, and `Database Analysis` — appears **only when it has at least one item**. Never emit `None.` / `Not applicable.` / `n/a` / `100%` placeholders for empty sections or omitted coverage surfaces; drop the whole heading and body instead. **History across CR runs** is preserved by the chronological sequence of always-new PR comments — never re-create a `Previous CR Status` section in the body.
- **`## Architecture` section (issue #530).** On Laravel projects (`laravel/framework` is in `composer.json` `require`), the architecture walk-through defined in `@skills/code-review/SKILL.md` Core Analysis runs on every CR run, but the `## Architecture` heading is rendered **only when the walk produces at least one finding**. When findings exist, render the heading and list them. When the walk is clean, omit the heading entirely — never render a `walked, 0 findings` status line, a `clean` placeholder, or any other confirmation that the check ran. On non-Laravel projects, omit the `## Architecture` section entirely.
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
- When the diff touches database operations (per the trigger list in `@skills/code-review/SKILL.md` Specialized Reviews), the posted PR comment must include a dedicated `## Database Analysis` section **before** `## Coverage`. The section reports only the `mysql-problem-solver` findings (with severity mirroring Critical / Moderate / Minor) and the proposed query rewrite / index reuse / batching fix per `@rules/sql/optimalize.mdc`. Do not include the queries / migrations inspected list or any EXPLAIN / static-analysis summary — those stay inside the internal investigation. When no DB operations are present, omit the section entirely.
- The posted PR comment includes a `## Coverage` section before the summary line **only** when the coverage gate has something to report — uncovered changed lines (Critical findings) or unavailable / non-runnable coverage tooling (Critical finding). When every changed line is at 100% coverage and the tool ran successfully, omit the `## Coverage` section, the `Coverage:` header line, and the `coverage …` slot from the summary line per `@skills/code-review/SKILL.md` Output Rules. The coverage gate itself (per the Coverage gate in `@skills/code-review/SKILL.md`) still runs on every review; only the user-visible section is short-circuited.
- The PR comment summary line must report the issue-tracker summary status — `posted summary to issue #N` (or comma-separated list when multiple), `no linked issue — issue summary skipped`, or `failed to post on issue #N: <reason>` when a permission / network error occurs. Never post a CR comment without it.
- End with summary line

## Output Format

Use the template defined in `templates/pr-comment-output.md`.
