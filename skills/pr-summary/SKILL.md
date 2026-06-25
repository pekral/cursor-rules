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
- Apply @rules/reports/general.mdc — the published comment must be written in the language of the source assignment (Czech assignment → Czech comment; English assignment → English comment). Code identifiers stay verbatim per the rule's *Scope clarifications*.
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Focus on the "why" and business impact, not on implementation details — but keep enough technical context (which integration, payload, table, endpoint, etc.) that a developer can still follow what changed.
- Do not include code snippets, file paths, line numbers, or diff fragments. The summary is for humans, not for static analysis.
- **GitHub target only — always credit the real change author(s)** (the JIRA non-technical comment omits the `Authors` line entirely; this metadata applies to the GitHub PR comment / linked-GitHub-issue mirror). Credit the real change author(s), not the agent or identity running the CR / publishing step. Extract authors from git commit history (`git log --pretty='%an <%ae>' base..HEAD | sort -u`) and from PR metadata (`author.login` and `commits[].author.login` returned by `skills/code-review-github/scripts/load-issue.sh`). When the target is GitHub, prefer `@github-handle`; when the target is JIRA, prefer the JIRA-account display name returned by the JIRA loader, otherwise fall back to the git `Name <email>`. Multiple authors are listed comma-separated in commit order. Never silently drop the Authors line — when authorship cannot be determined, write *"Authors: unknown — git history did not yield a recognisable identity"*.
- **Always flag changes that are reachable only behind a test / opt-in parameter** (feature flag, ENV switch, query-string parameter, request header, A/B variant, beta toggle, allow-listed account). When the diff shows a guard such as `config('feature.x')`, `env('SOMETHING_ENABLED')`, GrowthBook / Unleash / LaunchDarkly check, a query-string `?debug=`, a request header gate, or a hard-coded allow-list, surface the exact toggle and the value required to reach the change: on the **GitHub target** as an *"Available behind"* line; on the **JIRA target** folded into `How to test` step 1 (which enables the toggle before the tester proceeds). Omit it only when the change is reachable by every user unconditionally.
- **Output depends on the target tracker:**
  - **GitHub target** — output **the two required sections plus the two metadata lines** defined in `templates/pr-summary-github.md`: `Authors`, the conditional `Available behind`, `Summary of changes`, and `How to test`. No categories, no breaking-changes section, no testing-notes section.
  - **JIRA target** — output **only `How to test`** plus the conditional embedded blocks (see below). The JIRA non-technical comment is intentionally minimal: no `Authors` line, no `Summary of changes` section, no `Available behind` metadata line. When the change is reachable only behind a test parameter, fold that toggle into `How to test` step 1 instead of a separate line. The JIRA audience gets exactly how to test the change, and nothing else unless the wrapper passes a clarifying-questions or assignment-compliance block.
- **No leaked markup on JIRA.** When the target is JIRA, the rendered body must contain **only** JIRA Wiki Markup — never a Markdown control character that JIRA would show as literal text. Before publishing, scan the body and convert / reject any `**bold**` / `__bold__` (→ `*bold*`), `#`/`##`/`###` ATX headings (→ `h1.`/`h2.`/`h3.`), `` `code` `` (→ `{{code}}`), fenced ```` ``` ```` blocks (→ `{code}…{code}`), `- ` / `+ ` bullets (→ `*`), and Markdown links `[label]` + `(url)` (→ `[label|url]`) per `@rules/jira/general.mdc`. The reader must never see a raw `**` or `#`.
- **Embedded blocks (consolidation contract — issue #498):** when the calling CR wrapper passes extra markdown blocks (the `Clarifying questions` block and/or the `Assignment Compliance` block returned by `@skills/assignment-compliance-check/SKILL.md`), append them **verbatim** after `How to test` and **before** the template's signature footer. Each embedded block must already be in the target tracker's markup (GitHub Markdown for GitHub, JIRA Wiki Markup for JIRA — the wrapper converts before passing). The resulting comment is published once per linked tracker target — that single consolidated comment is the only non-technical artifact a CR run posts on each linked issue or JIRA ticket. When no embedded blocks are passed, the template renders without that slot exactly as before.
- **Assignment non-compliance verdict (top banner):** whenever the calling CR wrapper passes an `Assignment Compliance` embedded block — i.e. the changes do **not** satisfy the assignment — render a single prominent verdict line at the **very top** of the comment (the `{assignment_verdict}` slot), in the assignment language, stating the non-compliance and the gap count `N`, and pointing to the `Assignment Compliance` detail below. This guarantees the reader sees the assignment was not met without scrolling to the appended block. Derive `N` from the passed block (`Critical gaps found: N` verdict line, or the number of gap entries). When no `Assignment Compliance` block is passed (the changes satisfy the assignment, or no tracker is linked), omit the slot entirely — never render a positive "satisfies the assignment" banner, consistent with the report-only-what-needs-action convention.

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
8. Write the summary using the chosen template:
   - **GitHub target** — fill the metadata lines and both required sections:
     - **Authors** — comma-separated identities resolved in step 5.
     - **Available behind** *(conditional)* — toggle name + value required to reach the change, as resolved in step 6.
     - **Summary of changes** — one short headline naming the change, followed by a single paragraph (3–5 sentences) that explains the business reason, the affected area, and the technical context in plain language. Phrase it impersonally ("The change …", "This update …") so multiple credited authors stay accurate; do not write it in singular first person.
     - **How to test** — an ordered list of concrete steps a tester can follow end-to-end to verify the change works. Each step must be an action the tester performs or an outcome they verify. When *Available behind* is set, the **first** test step must be to enable / supply the gating toggle. **When the caller (e.g. `apollon` in light reporting mode) passes pre-authored test steps derived from designed test scenarios, use those steps directly instead of auto-generating from the diff.** Pre-authored steps take precedence: the caller's scenarios are the source of truth for `How to test` in that case.
   - **JIRA target** — fill **only** `How to test` (the same ordered, end-to-end test steps). Do **not** render `Authors`, `Summary of changes`, or an `Available behind` line. When test-parameter gating was detected in step 6, the **first** `How to test` step enables / supplies the toggle. Everything else the JIRA reader sees comes from the conditional embedded blocks (clarifying questions, assignment compliance) the wrapper passes — never authored here.
9. **Embedded blocks slot:** if the caller passed embedded markdown blocks, place them **between** the `How to test` section and the template's signature footer, separated by a single blank line. Render each block exactly as received — no re-formatting, no language conversion (the caller already converted to the target tracker's markup), no re-ordering. The result is a single consolidated comment per linked tracker target.
10. **Assignment verdict slot:** if one of the embedded blocks is an `Assignment Compliance` block, render the `{assignment_verdict}` line at the very top of the comment (before `Authors` on GitHub, before `How to test` on JIRA), in the assignment language, naming the gap count `N` derived from that block and pointing to the detail below — e.g. `⚠️ **Changes do not satisfy the assignment — N gap(s). See Assignment Compliance below.**` (Czech → `⚠️ **Změny nesplňují zadání — N nedostatk(ů). Viz Assignment Compliance níže.**`). Omit the slot entirely when no `Assignment Compliance` block was passed.

**Output format:**

- For GitHub PR comments use the template defined in `templates/pr-summary-github.md` (full shape: Authors / Available behind / Summary of changes / How to test).
- For JIRA issue comments use the template defined in `templates/pr-summary-jira.md` — the **reduced** shape: only `How to test` plus any conditional embedded blocks (Clarifying questions, Assignment Compliance). Do **not** translate the Wiki Markup back to Markdown when posting via `acli` / JIRA MCP server — JIRA UI does not render Markdown, and no raw Markdown control character may leak into the body.

**After completing the tasks**
- Post the summary as a comment to the related PR or issue if available, using the template that matches the target tracker.
- **Publishing contract:** publish through the shared helpers so each tracker receives its tracker-native markup — never via raw `gh issue comment` / `gh pr comment` / `acli jira workitem comment add` calls.
  - **GitHub target** (PR comment or linked-GitHub-issue mirror): pipe the rendered body into `skills/code-review-github/scripts/upsert-comment.sh <NUMBER|URL> -`. The helper detects the current GitHub actor (`gh api user --jq .login`), appends the marker `<!-- cr-comment:actor=<gh-login> -->` for traceability, and **POSTs a fresh comment on every run** (the helper never PATCHes a prior comment in place). Fall back to the GitHub MCP server's `addIssueComment` only when the helper exits with code 2 (missing tool) or 3 (API failure) — also as a fresh post; never call `updateIssueComment` to edit a previous CR / pr-summary comment.
  - **JIRA target**: pipe the rendered body into `skills/code-review-jira/scripts/upsert-comment.sh <KEY|URL> -`. The helper POSTs a new comment on every run — it never edits a prior comment in place. Fall back to the JIRA MCP server's `addCommentToJiraIssue` only when the helper exits with code 2 (missing tool) or 3 (API failure) — also as a fresh post.
  - Pre-existing comments published before these conventions were introduced are left untouched.
  - Log the action (`created`) plus the resulting comment URL in the CR wrapper's summary line.

---

## Principles
- Focus on business impact, not technical detail
- Explain the "why" and just enough "what" so a developer can locate the change without reading the diff
- Be concise — the whole comment fits on one screen
- Make the test steps reproducible by a non-developer tester
- Match the formatting to the target tracker (Markdown for GitHub, Wiki Markup for JIRA)

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
