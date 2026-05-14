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
- Never modify code
- GitHub output must be in English
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
  - DB changes → @skills/mysql-problem-solver/SKILL.md — when reviewing new or modified SQL / Eloquent / query-builder queries, prefer rewriting the query to reuse an existing schema index over proposing a new one (see `@rules/sql/optimalize.mdc` "Reuse existing indexes first")
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
- Never post file paths, line numbers, code snippets, or technical severity levels to JIRA
- Post a plain-language summary:
  - overall status (clean / has issues)
  - key risks described in business terms
  - testing recommendations with step-by-step instructions
  - link to the GitHub PR for full technical details

#### Linked GitHub issues (non-technical summary)
- If the reviewed PR also references a GitHub issue (i.e. `closingIssues[]` of the GitHub PR JSON is non-empty), post the **same plain-language summary** as a comment on every linked GitHub issue using `gh issue comment <number> --body ...`.
- Mirror the JIRA non-technical content above (overall status, key risks in business terms, testing recommendations, link to the PR). No file paths, no line numbers, no code, no severity labels.
- The JIRA-side summary is the primary tracker comment; the GitHub-issue comment is a courtesy mirror so reviewers reading the GitHub issue see the same conclusion without opening JIRA.
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
- The posted PR comment must always include a `## Coverage` section before the summary line. The section reports the tool used, command run, and coverage result for changed lines (or "tooling unavailable" with reason). Never post a CR comment without it.
- Use the template defined in `templates/github-output.md`

### JIRA (non-technical summary — only here)
- Never include file paths, line numbers, code snippets, or technical severity levels
- Write in plain language understandable by non-developers
- **Format the comment as JIRA Wiki Markup**, not GitHub-flavoured Markdown — JIRA UI does not render Markdown headings (`#`), bold (`**`), fenced code blocks (` ``` `), Markdown links (`[label](url)`), or Markdown tables (`|...|`). The reviewer must see the comment formatted in the JIRA web UI, not raw text. Conversion cheatsheet:
    - Heading: `## Heading` → `h2. Heading` (`### Heading` → `h3. Heading`)
    - Bold: `**bold**` → `*bold*`
    - Italic: `*italic*` or `_italic_` → `_italic_`
    - Inline code: `` `code` `` → `{{code}}`
    - Code block: ` ```php ... ``` ` → `{code:php} ... {code}`
    - Bullet list: `- item` → `* item`
    - Numbered list: `1. item` → `# item`
    - Link: `[label](https://example.com)` → `[label|https://example.com]`
    - Quote: `> text` → `{quote}text{quote}`
- Use the template defined in `templates/jira-output.md` — that template is already written in JIRA Wiki Markup; do not "translate" it back to Markdown when posting via `acli` / JIRA MCP server.

---

## Principles

- Focus on risks, not style
- Prefer impact over quantity
- Avoid duplication of findings
- Prioritize regression detection
- Be precise and actionable

---

## After Completion

- Always run @skills/test-like-human/SKILL.md, regardless of code review findings.
