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
  - run @skills/assignment-compliance-check/SKILL.md — non-technical business-logic vs assignment check. The skill publishes the **Assignment Compliance** report as a dedicated JIRA comment on the originating ticket (converted to JIRA Wiki Markup per `@rules/jira/general.mdc`) and returns only a status string (`posted to <KEY>`, `no linked issue — assignment compliance skipped`, or `failed to publish …`). **Do not embed** the report into the GitHub PR comment and **do not duplicate** its Critical gaps inside the JIRA non-technical summary — the dedicated compliance comment carries that content. Surface the returned status in the GitHub PR comment summary line.
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

#### GitHub (technical findings only)
- If a previous CR exists for the same PR, analyze all previous findings and classify each as: **Resolved**, **Deferred**, or **Still open**
- Include a **Previous CR Status** section at the top of the GitHub comment (before new findings)
- Post all technical findings as PR comment
- Format:
  - Critical → Moderate → Minor → Refactoring (DRY / Tech Debt Reduction)
  - file + line
  - actionable fix
- Post all technical findings inside the single PR comment — never as line-anchored review comments. Include the `file:line` reference in the body of each finding instead.
- This is the only place where technical details appear

#### JIRA (non-technical summary only)
- Delegate the non-technical JIRA comment to `@skills/pr-summary/SKILL.md`. This CR skill must not author its own JIRA summary — the goal is a uniform *"Authors / Available behind / Summary of changes / How to test"* output that non-technical project managers understand and can act on, identical to what `pr-summary` produces for any other audience.
- When invoking `pr-summary`, pass through the PR `author.login` + `commits[].author.login` set and the git `%an <%ae>` log so the published JIRA comment credits the **real change author(s)** (JIRA display name when the JIRA loader can match a committer; otherwise the GitHub handle; otherwise the git `Name <email>`). Never the agent / CR identity. Confirm the `Authors` line is present in the published comment.
- When invoking `pr-summary`, also pass through any **test-parameter gating** detected in the diff (feature flag, ENV switch, query-string parameter, request header, admin toggle, allow-list) so the published comment carries the `Available behind` line and folds the toggle-enabling step into `How to test` step 1.
- Invoke `@skills/pr-summary/SKILL.md` with the **JIRA** tracker target so it renders `@skills/pr-summary/templates/pr-summary-jira.md` in JIRA Wiki Markup and posts the comment on the originating JIRA ticket via `acli` (JIRA MCP server fallback).
- Never post file paths, line numbers, code snippets, technical severity levels, or finding counts to JIRA — `pr-summary` already enforces this constraint by design; technical content stays exclusively on the GitHub PR comment.
- When the CR run yields Critical / Moderate findings that block merge, surface that signal in the GitHub PR comment summary line; the JIRA comment stays focused on what changed and how to test it.

#### Linked GitHub issues (non-technical summary)
- If the reviewed PR also references a GitHub issue (i.e. `closingIssues[]` of the GitHub PR JSON is non-empty), delegate the linked-GitHub-issue comment to `@skills/pr-summary/SKILL.md` (GitHub tracker target). The skill renders `@skills/pr-summary/templates/pr-summary-github.md` in GitHub Markdown and posts via `gh issue comment <number>` on each entry in `closingIssues[]`. Pass the same author + test-parameter-gating context as the JIRA invocation above — the mirrored comment must carry the same `Authors` and `Available behind` lines.
- The JIRA-side summary is the primary tracker comment; the GitHub-issue comment is a courtesy mirror so reviewers reading the GitHub issue see the same *"Summary of changes + How to test"* output without opening JIRA. Both comments come from `pr-summary`, so they are guaranteed to match.
- If `closingIssues[]` is empty, skip this block and note "no linked GitHub issue — mirror skipped" in the PR comment summary line.
- If `gh issue comment` returns a permission error (cross-repo issue, lacking write access), log the failure in the PR comment summary line and continue — do not abort the review.

---

## Output Rules

### GitHub (technical report — only here)
- All technical findings go exclusively to GitHub PR comments
- Include: file paths, line numbers, code references, severity levels, concrete fixes
- Findings only — no praise, no explanations of what was checked
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
