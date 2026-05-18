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
- Match the language of the target tracker (issue/PR description): write the comment in the same language a reader of the target would expect.
- Focus on the "why" and business impact, not on implementation details — but keep enough technical context (which integration, payload, table, endpoint, etc.) that a developer can still follow what changed.
- Do not include code snippets, file paths, line numbers, or diff fragments. The summary is for humans, not for static analysis.
- **Always credit the real change author(s)**, not the agent or identity running the CR / publishing step. Extract authors from git commit history (`git log --pretty='%an <%ae>' base..HEAD | sort -u`) and from PR metadata (`author.login` and `commits[].author.login` returned by `skills/code-review-github/scripts/load-issue.sh`). When the target is GitHub, prefer `@github-handle`; when the target is JIRA, prefer the JIRA-account display name returned by the JIRA loader, otherwise fall back to the git `Name <email>`. Multiple authors are listed comma-separated in commit order. Never silently drop the Authors line — when authorship cannot be determined, write *"Authors: unknown — git history did not yield a recognisable identity"*.
- **Always flag changes that are reachable only behind a test / opt-in parameter** (feature flag, ENV switch, query-string parameter, request header, A/B variant, beta toggle, allow-listed account). When the diff shows a guard such as `config('feature.x')`, `env('SOMETHING_ENABLED')`, GrowthBook / Unleash / LaunchDarkly check, a query-string `?debug=`, a request header gate, or a hard-coded allow-list, the comment **must** include an *"Available behind"* line naming the exact toggle and the value required to reach the change. Omit the line only when the change is reachable by every user unconditionally.
- Output **the two required sections plus the two metadata lines** defined in the chosen template — `Authors`, `Summary of changes`, `How to test`, and the conditional `Available behind`. No categories, no breaking-changes section, no testing-notes section.

**Steps:**
1. Identify the current branch and its base branch (usually `master` or `main`).
2. Load all commits in the current branch since it diverged from the base branch (`git log base..HEAD`).
3. For each commit, read the commit message and the diff to understand what changed and why.
4. If a PR already exists for this branch, load the PR description and linked issue(s) for additional context (business motivation, acceptance criteria, reporter's expectations):
   - **GitHub:** `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — read `body`, `comments[]`, `author`, `commits[].author`, and `closingIssues[]` off the resulting JSON document.
   - **JIRA:** `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>` — read `descriptionText`, `comments[]`, `assignee`, `reporter`, and linked PRs.
   - Never call `gh pr view`, `gh issue view`, or `acli` directly; fall back to the GitHub / JIRA MCP server only when the loader is unavailable (exit code 2/3).
5. **Resolve the real change author(s):**
   - Run `git log --pretty='%an <%ae>' base..HEAD | awk 'NF' | sort -u` to collect commit authors.
   - When PR metadata is available, also collect `author.login` and the unique `commits[].author.login` set — these give GitHub handles that are preferred over the raw `Name <email>` form when the target tracker is GitHub.
   - When the target tracker is JIRA and the PR commit author email matches a known JIRA account (via the JIRA loader's user lookup or `assignee` / `reporter` matching the committer), prefer the JIRA display name.
   - Build the **Authors** line: comma-separated identities in commit order, deduped, prefixed with `@` for GitHub handles. If no identity could be resolved, fall back to *"unknown — git history did not yield a recognisable identity"*.
6. **Detect test-parameter gating:** scan the diff for guards that hide the change from default users — `config('…')` / `env('…')` toggles, GrowthBook / Unleash / LaunchDarkly flag checks, query-string parameters (`?debug=`, `?preview=`), request headers (`X-Beta-…`), middleware allow-lists (`Auth::user()->isInternal()`), feature-flag attributes, A/B variant branches. For every guard found, record the toggle name, the value required to reach the change, and any documented switch label (admin screen, ENV var). Populate the conditional **Available behind** line; omit it only when no guard exists on the path to the change.
7. Detect the **target tracker** for the comment by following the table in `@skills/resolve-issue/references/source-detection.md` (branch name / PR description / linked issue trail):
   - **JIRA** — the branch or PR description matches a JIRA issue-key regex (e.g. `^[A-Z][A-Z0-9_]+-\d+$`), or the JIRA loader from step 4 returns a non-empty document. Use `templates/pr-summary-jira.md` (JIRA Wiki Markup).
   - **GitHub** — otherwise, or when the user explicitly asks for a PR comment. Use `templates/pr-summary-github.md` (GitHub Markdown).
   - If both signals match (cross-tracker PR), prefer the tracker named in the user's invocation; if none was given, prefer JIRA so the JIRA UI receives a formatted comment.
8. Write the summary using the chosen template. Fill the metadata lines and both required sections:
   - **Authors** — comma-separated identities resolved in step 5.
   - **Available behind** *(conditional)* — toggle name + value required to reach the change, as resolved in step 6.
   - **Summary of changes** — one short headline naming the change, followed by a single paragraph (3–5 sentences) that explains the business reason, the affected area, and the technical context in plain language. Phrase it impersonally ("The change …", "This update …") so multiple credited authors stay accurate; do not write it in singular first person.
   - **How to test** — an ordered list of concrete steps a tester can follow end-to-end to verify the change works. Each step must be an action the tester performs or an outcome they verify. When *Available behind* is set, the **first** test step must be to enable / supply the gating toggle.

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
