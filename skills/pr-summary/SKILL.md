---
name: pr-summary
description: "Use when summarizing current PR changes for the development and product team. Analyzes all commits in the current branch, explains the purpose of changes, and produces a clear human-readable report that can be posted either as a GitHub PR comment (Markdown) or as a JIRA comment (Wiki Markup)."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/php/core-standards.mdc
- Apply @rules/git/general.mdc
- Apply @rules/jira/general.mdc when the target is a JIRA issue
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Write the summary in singular first person (one developer made the changes).
- Match the language of the target tracker (issue/PR description): write the comment in the same language a reader of the target would expect.
- Focus on the "why" and business impact, not on implementation details — but keep enough technical context (which integration, payload, table, endpoint, etc.) that a developer can still follow what changed.
- Do not include code snippets, file paths, line numbers, or diff fragments. The summary is for humans, not for static analysis.
- Output **only the two sections** defined in the chosen template — `Summary of changes` and `How to test`. No categories, no breaking-changes section, no testing-notes section.

**Steps:**
1. Identify the current branch and its base branch (usually `master` or `main`).
2. Load all commits in the current branch since it diverged from the base branch (`git log base..HEAD`).
3. For each commit, read the commit message and the diff to understand what changed and why.
4. If a PR already exists for this branch, load the PR description and linked issue(s) for additional context (business motivation, acceptance criteria, reporter's expectations):
   - **GitHub:** `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — read `body`, `comments[]`, and `closingIssues[]` off the resulting JSON document.
   - **JIRA:** `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>` — read `descriptionText`, `comments[]`, and linked PRs.
   - Never call `gh pr view`, `gh issue view`, or `acli` directly; fall back to the GitHub / JIRA MCP server only when the loader is unavailable (exit code 2/3).
5. Detect the **target tracker** for the comment by following the table in `@skills/resolve-issue/references/source-detection.md` (branch name / PR description / linked issue trail):
   - **JIRA** — the branch or PR description matches a JIRA issue-key regex (e.g. `^[A-Z][A-Z0-9_]+-\d+$`), or the JIRA loader from step 4 returns a non-empty document. Use `templates/pr-summary-jira.md` (JIRA Wiki Markup).
   - **GitHub** — otherwise, or when the user explicitly asks for a PR comment. Use `templates/pr-summary-github.md` (GitHub Markdown).
   - If both signals match (cross-tracker PR), prefer the tracker named in the user's invocation; if none was given, prefer JIRA so the JIRA UI receives a formatted comment.
6. Write the summary using the chosen template. Fill both sections:
   - **Summary of changes** — one short headline naming the change, followed by a single paragraph (3–5 sentences) that explains the business reason, the affected area, and the technical context in plain language.
   - **How to test** — an ordered list of concrete steps a tester can follow end-to-end to verify the change works. Each step must be an action the tester performs or an outcome they verify.

**Output format:**

- For GitHub PR comments use the template defined in `templates/pr-summary-github.md`.
- For JIRA issue comments use the template defined in `templates/pr-summary-jira.md`. Do **not** translate the Wiki Markup back to Markdown when posting via `acli` / JIRA MCP server — JIRA UI does not render Markdown.

**After completing the tasks**
- Post the summary as a comment to the related PR or issue if available, using the template that matches the target tracker.

---

## Principles
- Focus on business impact, not technical detail
- Explain the "why" and just enough "what" so a developer can locate the change without reading the diff
- Be concise — the whole comment fits on one screen
- Make the test steps reproducible by a non-developer tester
- Match the formatting to the target tracker (Markdown for GitHub, Wiki Markup for JIRA)

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
