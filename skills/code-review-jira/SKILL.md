---
name: code-review-jira
description: Use when run code review for JIRA issues and publish results to
  GitHub PR and JIRA
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Code Review (JIRA)

## Purpose
Perform code review for JIRA issues by analyzing related pull requests and publishing results to:
- GitHub (technical findings)
- JIRA (human-readable summary)

---

## Constraints
- Apply @rules/jira/general.mdc
- Apply @rules/git/general.mdc
- Apply @rules/reports/general.mdc. The **GitHub PR technical comment** this skill posts (Status / Counts / Findings / Refactoring / Database Analysis / Coverage / Summary) stays in canonical English per the rule's *Exception — technical CR findings on the GitHub PR*. The **JIRA comment** delegated to `@skills/pr-summary/SKILL.md` and the **mirrored linked-GitHub-issue summary** follow the language of the source JIRA assignment. Never mix languages inside the same comment; never use bilingual *Kritické (Critical)* style parentheses.
- **Read-only skill** — never modify code, never stage / commit / push changes, and never run any git write operation (`git add`, `git commit`, `git push`, `git reset`, `git checkout -- …`, etc.). Switching to the relevant branch and `git pull` to read the latest diff are allowed; mutating the working tree or pushing to the remote is not. Publishing is limited to PR / linked-issue comments via `gh` and to JIRA ticket comments via `acli`.
- JIRA output must be understandable for non-developers
- Output findings only (no praise)

---

## Execution

### 1. Load Context
- Load JIRA context by running `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>` — the single deterministic entry point. Never call `acli` directly. Read issue header, description, comments, attachments, subtasks, issue links, custom fields, `devSummary`, and `pullRequests` off the resulting JSON document.
- The script accepts a bare key (`ECOMAIL-1234`), a `/browse/<KEY>` URL, or any URL containing `?selectedIssue=<KEY>`.
- If the script is unavailable (missing tool, exit code 2/3) fall back to the JIRA MCP server. Always prefer the MCP fallback for data the script cannot cover: changelog (`expand=changelog`), available next transitions, and friendly custom-field names (`expand=names`).
- Identify all open PRs linked to the issue from the script's `pullRequests` array
- Before reviewing a PR, switch to the PR branch and pull latest changes

#### Issue Context Analysis
Before reviewing code, load and analyze the full JIRA issue:

1. Fetch the complete JIRA issue — description, all comments, and all attachments (screenshots, files, embedded data).
2. Extract from the issue:
   - **Requirements and acceptance criteria** — what the code must do
   - **Expected behavior** — how the feature or fix should work
   - **Edge cases and constraints** — mentioned by the reporter or in comments
   - **Test data** — any sample inputs, payloads, or scenarios provided in the issue
3. Use this context to evaluate whether the implementation fully satisfies the issue — not just whether the code is technically correct.
4. If the issue contains test data or test scenarios, verify they are covered by existing or new tests. Flag missing test coverage as a finding.

### 2. Pre-checks
- If PR has conflicts → skip review for that PR

### 3. Run Reviews

- For each PR:
  - run @skills/assignment-compliance-check/SKILL.md — non-technical business-logic vs assignment check. The skill **does not publish anywhere itself** — it returns the assembled `## Assignment Compliance` markdown block (the wrapper converts it to JIRA Wiki Markup before passing it to `pr-summary` for the JIRA target, and keeps GitHub Markdown for the linked-GitHub-issue mirror). When no linked tracker exists, the skill returns the status `no linked issue — assignment compliance skipped`. The CR wrapper passes the returned block as an embedded block to `@skills/pr-summary/SKILL.md` so each tracker (JIRA ticket, linked GitHub issue) receives **one consolidated comment** per CR run (per issue #498). **Do not embed** the block into the GitHub PR comment — surface only the consolidated-comment status in the PR comment summary line.
  - run @skills/code-review/SKILL.md
  - run @skills/security-review/SKILL.md
  - run @skills/class-refactoring/SKILL.md — read-only refactoring lens scoped to the PR diff. Surface DRY duplication and tech-debt-reducing changes only on lines actually touched by the PR.

- Run conditionally:
  - **Database operations detected in the diff → `@skills/mysql-problem-solver/SKILL.md` is mandatory.** Trigger pattern list is owned by `@skills/code-review/SKILL.md` Specialized Reviews (raw SQL, Eloquent / query-builder calls, eager loads, model scopes, ModelManager / Repository methods, migrations, seeders, DynamoDB / NoSQL access). Capture its findings and surface them on the GitHub PR comment under the dedicated `## Database Analysis` section (see Output Rules) — never silently fold them into the Critical / Moderate / Minor buckets. The JIRA non-technical comment **does not** carry this section (it stays plain-language via `pr-summary`).
  - Shared state → @skills/race-condition-review/SKILL.md
  - Third-party API or service changes → ensure the **Third-Party API & Service Analysis** step from `@skills/code-review/SKILL.md` is executed for the diff

#### Refactoring & Tech Debt (DRY) Analysis (PR diff only)

1. Restrict the analysis to lines added or modified in the PR — never review untouched code.
2. For each changed block, apply `@skills/class-refactoring/SKILL.md` and look for:
   - duplicated logic that already exists elsewhere (DRY) — verify the change reuses existing logic instead of introducing a parallel implementation, per `@rules/code-review/general.mdc` Reuse Existing Logic section
   - data shaping repeated across Actions/Services/controllers/jobs/listeners/Livewire/commands
   - oversized methods, deep nesting, mixed responsibilities introduced or amplified by the change
3. Each finding must include the file path, the affected line range, and a concrete refactoring that *reduces* tech debt.
4. In-scope refactorings go into the **Refactoring (DRY / Tech Debt Reduction)** section of the GitHub PR comment template. Out-of-scope structural problems still belong in **Refactoring Proposals**.

### 4. Publish Results

> **Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`):** when the caller explicitly requests "do not publish; return findings as in-memory markdown for this loop iteration only", **skip all publishing** below — no GitHub PR comment, no JIRA comment, no linked-GitHub-issue mirror. Return the assembled review markdown to the caller and stop. Only the final (publishing) call from `process-code-review` after convergence runs Publish Results in full.

#### GitHub (technical findings only — single-comment upsert per actor)
- Every CR run owns **exactly one PR comment per GitHub actor**. The comment is keyed by the marker `<!-- cr-comment:actor=<gh-login> -->` (auto-appended by the upsert helper) so follow-up runs **edit the existing comment in place** instead of stacking new comments — no quoting, no "Replying to …" threads, no detached follow-up posts. History across runs is preserved by GitHub's edit history; never re-create a `Previous CR Status` section in the body.
- Publish via `skills/code-review-github/scripts/upsert-comment.sh <PR-NUMBER|URL> -` (body on stdin). The helper detects the current actor (`gh api user --jq .login`), searches the PR comments for the marker, and either PATCHes the existing comment or POSTs a fresh one. The published URL is emitted on stdout; the action (`created|updated`) on stderr — log it in the PR comment summary line.
- If the upsert helper exits with code 2 (missing tool) or 3 (API failure), fall back to the GitHub MCP server: list comments, locate the marker, then `updateIssueComment` (or `addIssueComment` when absent). Never bypass the marker convention by posting through raw `gh issue comment` / `gh pr comment` — that path stacks duplicates and is forbidden.
- Pre-existing CR comments published before this convention was introduced are left untouched. The first marker-scoped run after the change creates a clean comment alongside them; no migration / deletion is performed.
- Format inside the comment body:
  - Critical → Moderate → Minor → Refactoring (DRY / Tech Debt Reduction)
  - file + line
  - actionable fix
- Post all technical findings inside the single PR comment — never as line-anchored review comments. Include the `file:line` reference in the body of each finding instead.
- This is the only place where technical details appear.

#### JIRA (consolidated non-technical comment — single-comment upsert per actor)
- Delegate the JIRA comment to `@skills/pr-summary/SKILL.md`. This CR skill must not author its own JIRA summary — the goal is a uniform *"Authors / Available behind / Summary of changes / How to test"* output that non-technical project managers understand and can act on, identical to what `pr-summary` produces for any other audience.
- **Consolidation contract (issue #498):** invoke `pr-summary` exactly once for the JIRA ticket and pass it the `## Assignment Compliance` markdown block returned by `@skills/assignment-compliance-check/SKILL.md` — first convert that block to JIRA Wiki Markup per `@rules/jira/general.mdc` (`## ` → `h2. `, `**bold**` → `*bold*`, etc.) — as an embedded block. `pr-summary` appends the block verbatim after `How to test` and **upserts** the JIRA comment via `skills/code-review-jira/scripts/upsert-comment.sh` (JIRA MCP server fallback when `acli` lacks comment-edit support or exits with code 4). The upsert keeps **exactly one JIRA comment per (ticket, acli actor)** — keyed by the invisible anchor `{anchor:cr-comment-actor-<slug>}` (auto-prepended by the upsert helper) — so follow-up runs edit it in place instead of stacking new comments. Never post a separate JIRA comment for assignment compliance on top of it.
- When invoking `pr-summary`, pass through the PR `author.login` + `commits[].author.login` set and the git `%an <%ae>` log so the published JIRA comment credits the **real change author(s)** (JIRA display name when the JIRA loader can match a committer; otherwise the GitHub handle; otherwise the git `Name <email>`). Never the agent / CR identity. Confirm the `Authors` line is present in the published comment.
- When invoking `pr-summary`, also pass through any **test-parameter gating** detected in the diff (feature flag, ENV switch, query-string parameter, request header, admin toggle, allow-list) so the published comment carries the `Available behind` line and folds the toggle-enabling step into `How to test` step 1.
- Invoke `@skills/pr-summary/SKILL.md` with the **JIRA** tracker target so it renders `@skills/pr-summary/templates/pr-summary-jira.md` in JIRA Wiki Markup and upserts the comment on the originating JIRA ticket through the helper above (no direct `acli jira workitem comment add` calls that bypass the marker).
- Never post file paths, line numbers, code snippets, technical severity levels, or finding counts to JIRA — `pr-summary` already enforces this constraint by design, and the embedded `Assignment Compliance` block obeys the same rule. Technical content stays exclusively on the GitHub PR comment.
- When the CR run yields Critical / Moderate findings that block merge, surface that signal in the GitHub PR comment summary line; the consolidated JIRA comment stays focused on what changed, how to test it, and whether the assignment was fulfilled.

#### Linked GitHub issues (consolidated mirror — single-comment upsert per linked issue per actor)
- If the reviewed PR also references a GitHub issue (i.e. `closingIssues[]` of the GitHub PR JSON is non-empty), delegate the linked-GitHub-issue comment to `@skills/pr-summary/SKILL.md` (GitHub tracker target). The skill renders `@skills/pr-summary/templates/pr-summary-github.md` in GitHub Markdown and **upserts** it via `skills/code-review-github/scripts/upsert-comment.sh` on each entry in `closingIssues[]` (one comment per linked issue, per actor — keyed by `<!-- cr-comment:actor=<gh-login> -->`). Pass the same author + test-parameter-gating context as the JIRA invocation above — the mirrored comment must carry the same `Authors` and `Available behind` lines.
- **Consolidation contract (issue #498):** pass the GitHub-Markdown version of the `## Assignment Compliance` markdown block returned by `@skills/assignment-compliance-check/SKILL.md` as an embedded block so the linked-GitHub-issue audience also sees **one consolidated comment** per CR run instead of two separate posts.
- The JIRA-side summary is the primary tracker comment; the GitHub-issue comment is a courtesy mirror so reviewers reading the GitHub issue see the same *"Summary of changes + How to test + Assignment Compliance"* output without opening JIRA. Both comments come from `pr-summary`, so they are guaranteed to match.
- If `closingIssues[]` is empty, skip this block and note "no linked GitHub issue — mirror skipped" in the PR comment summary line.
- If the upsert helper or the GitHub MCP fallback returns a permission error (cross-repo issue, lacking write access), log the failure in the PR comment summary line and continue — do not abort the review.

---

## Output Rules

### GitHub (technical report — only here)
- All technical findings go exclusively to GitHub PR comments
- Include: file paths, line numbers, code references, severity levels, concrete fixes
- Findings only — no praise, no explanations of what was checked
- **Omit empty sections entirely.** Only the header block (Status / Counts / Coverage / Last updated / Linked-tracker mirror), the `## Coverage` section, and the final `Summary` line are always rendered in the GitHub PR comment. Every other section — `Findings` (including each severity sub-heading), `Refactoring (DRY / tech debt)`, `Refactoring proposals`, and `Database Analysis` — appears **only when it has at least one item**. Never emit `None.` / `Not applicable.` / `n/a` placeholders for empty sections; drop the whole heading and body instead. The Counts line is the single source of "zero" signal so a clean review stays scannable. **History across CR runs** is preserved by GitHub's edit history on the upserted comment — never re-create a `Previous CR Status` section in the body.
- Use severity levels: Critical, Moderate, Minor
- Each **Critical** and **Moderate** finding must include:
    - **Faulty Example** — minimal code snippet or input payload reproducing the issue (redact secrets/PII)
    - **Expected Behavior** — single assertable statement (return value, exception, persisted state, emitted event)
    - **Test Hint** — one sentence pointing at the test layer (unit, integration, feature) and entry point
    - **Suggested Fix** — minimal corrected code snippet that resolves the finding. Must comply with `@rules/php/core-standards.mdc` and, for Laravel projects, `@rules/laravel/architecture.mdc`. Use `n/a — <reason>` only when a snippet adds no value over the one-line Fix description (e.g. naming-only changes, dead-code removal, pointers to an existing helper whose name already says enough).
- These four fields exist so `@skills/process-code-review/SKILL.md` can convert each finding into a reproducer test and apply the fix directly from the PR comment.
- Minor findings may omit these fields when no behavior change is implied.
- When the diff touches database operations (per the trigger list in `@skills/code-review/SKILL.md` Specialized Reviews), the posted GitHub PR comment must include a dedicated `## Database Analysis` section **before** `## Coverage`. The section reports only the `mysql-problem-solver` findings (with severity mirroring Critical / Moderate / Minor) and the proposed query rewrite / index reuse / batching fix per `@rules/sql/optimalize.mdc`. Do not include the queries / migrations inspected list or any EXPLAIN / static-analysis summary — those stay inside the internal investigation. When no DB operations are present, omit the section entirely. The JIRA non-technical comment (produced by `pr-summary`) never includes this section.
- The posted PR comment must always include a `## Coverage` section before the summary line. The section reports the **diff-scoped** script discovered (per the discovery order in `@skills/code-review/SKILL.md` Coverage gate — `vendor/bin/test-coverage-diff` from this package, Phing `test:coverage:diff` / `coverage:diff`, Composer `test:coverage:diff`, or any project-specific `*coverage*diff*` script), the exact command run, and the coverage result for changed lines (or "diff-scoped tooling unavailable" with reason). Never substitute full-suite coverage output (`composer test:coverage`, `pest --coverage` on the whole suite, Phing `coverage`) and never post a CR comment without this section.
- Use the template defined in `templates/github-output.md`

### JIRA (non-technical summary — only here)
- The non-technical JIRA comment is **produced and posted by `@skills/pr-summary/SKILL.md`**, not by this skill. Invoke `pr-summary` with the JIRA tracker target; do not author or embed a custom template here.
- `pr-summary` enforces the no-file-paths / no-line-numbers / no-code-snippets / no-severity-jargon contract by design — plain language understandable by non-developers, in two sections: *Summary of changes* and *How to test*.
- The JIRA Wiki Markup conversion (`h2.` / `h3.` headings, `*bold*`, `_italic_`, `{{inline}}`, `{code:php} ... {code}`, `*` / `#` bullets, `[label|url]`, `{quote}`) is handled by `@skills/pr-summary/templates/pr-summary-jira.md` per `@rules/jira/general.mdc`. Do not "translate" the output back to GitHub Markdown when posting via `acli` / JIRA MCP server.

---

## Principles

- Focus on risks, not style
- Prefer impact over quantity
- Avoid duplication of findings
- Prioritize regression detection
- Be precise and actionable

---

## After Completion

- Do **not** auto-invoke `@skills/test-like-human/SKILL.md`. The user-perspective testing skill runs **on demand only** (via `/test-like-human` or an explicit follow-up); CR-track skills must never chain into it.
