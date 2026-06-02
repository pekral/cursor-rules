---
name: assignment-compliance-check
description: "Use when checking that the pull request implementation actually fulfills the business requirements stated in the linked issue or task. Returns a plain-language markdown block listing only Critical functional gaps **only when at least one gap exists**; when the implementation satisfies every stated requirement the skill returns a skip status and the calling CR wrapper embeds nothing. The block (when present) carries actionable gaps only — never lists of satisfied requirements or open questions. No local file is created and the block is not embedded in the GitHub PR comment."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Apply `@rules/jira/general.mdc`
- Apply `@rules/reports/general.mdc` — the **Assignment Compliance** markdown block this skill returns to the caller must be written in the language of the linked assignment (Czech issue / JIRA description → Czech block; English → English). Linked-task / PR URLs, author handles, and severity labels follow the rule's *Scope clarifications*.
- The skill **must not** write any output to disk. It also **must not** publish anywhere itself — no `gh issue comment`, no `acli`, no JIRA / GitHub MCP write call. The skill returns either the assembled markdown block (when at least one Critical gap exists) or a skip status (when no Critical gaps exist, or when no linked tracker is detected) to the calling CR wrapper. The wrapper embeds the block into the **single consolidated linked-tracker comment** authored by `@skills/pr-summary/SKILL.md` (one comment per linked issue / JIRA ticket per CR run — see issue #498) **only when a block is returned**; on a skip status the wrapper embeds nothing and surfaces the status only on the PR comment summary line.
- The block **must not** be embedded into the GitHub PR comment produced by `@skills/code-review/SKILL.md`, `@skills/code-review-github/SKILL.md`, or `@skills/code-review-jira/SKILL.md`. The PR comment carries technical findings; the linked-tracker comment carries assignment compliance as part of the consolidated `pr-summary` output.
- The published block (when returned) must be plain language understandable by a non-technical reader. Include a short example for every Critical gap. **Do not list satisfied requirements, "what is working", or open questions for the reviewer** — the block reports only items that still need action.
- The published comment **must credit the real change author(s)** in the `Authors` line — resolved exactly as `@skills/pr-summary/SKILL.md` resolves them (git history `%an <%ae>` + PR `author.login` + `commits[].author.login`, JIRA display name when the target is JIRA). Never list the agent / CR identity. When authorship cannot be determined, write `unknown — git history did not yield a recognisable identity`.
- The published comment **must include the `Available behind` line whenever the change is reachable only behind a test parameter** (feature flag, ENV switch, query-string parameter, request header, A/B variant, admin toggle, allow-listed account). Detect the gating toggle the same way `@skills/pr-summary/SKILL.md` does (scan the diff for `config('…')` / `env('…')` checks, GrowthBook / Unleash / LaunchDarkly calls, query / header gates, allow-list middleware), name the toggle, and state the value required to reach the change. Omit the line entirely only when the change is reachable unconditionally.
- Report **only Critical** functional / business-logic gaps. Do not report architecture, code style, test coverage, refactoring opportunities, or any other concern — those are owned by the other review skills.
- Never modify code. This skill is read-only with respect to the codebase.
- Do not expose secrets, internal infrastructure paths, or PII in the comment.

## Use when
- A code review is being prepared for a PR linked to an issue or task (GitHub issue, JIRA ticket, Bugsnag report).
- A reviewer wants a focused "did the implementation do what the assignment asked for" check, separate from architecture / security / refactoring lenses.
- This skill is **invoked from every CR run** by `@skills/code-review/SKILL.md`, `@skills/code-review-github/SKILL.md`, and `@skills/code-review-jira/SKILL.md`.

## Required approach

### 1. Load the assignment
- Detect the originating tracker from the PR description / linked issue.
- **GitHub-originated:** run `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` against the linked issue. Read the full `body`, every entry in `comments[]` (including replies), and every referenced attachment URL.
- **JIRA-originated:** run `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>`. Read `descriptionText`, `comments[]`, and any attachment metadata.
- **Bugsnag-originated:** read the linked GitHub issue (the project mirrors Bugsnag errors to GitHub) and apply the GitHub branch.
- Never call `gh`, `acli`, or REST endpoints directly — always use the deterministic loaders.
- Group comments by thread. Discard outdated or superseded requirements (per the comment-analysis rules in `@skills/resolve-issue/SKILL.md`). Keep only the **current** requirements as the source of truth.

### 2. Extract verifiable requirements
For the assignment + current comments, enumerate:
- **Acceptance criteria** the implementation must satisfy (explicit "must" / "should" / numbered lists / Given-When-Then blocks).
- **Expected behavior** described in plain language (what the user should see / experience / receive).
- **Edge cases** named by the reporter or in comments.
- **Examples** the reporter provided (sample inputs, payloads, screenshots, expected outputs).

Skip generic developer hygiene wishes ("clean code", "tests please"). The check is strictly about business behavior described by the reporter.

### 3. Load the implementation
- Run `skills/code-review-github/scripts/load-issue.sh <PR-NUMBER>` for the PR and read `files[]`, `body`, and `commits[]`.
- For each extracted requirement from step 2, locate the matching change in the diff: the function, controller action, Livewire method, job, command, view, or test that should realize the requirement.
- If a requirement has no corresponding change in the diff, that is itself a Critical gap candidate (see step 4).

### 4. Cross-check requirement vs implementation
For every requirement from step 2, decide one of:
- **Satisfied** — the diff implements the behavior the assignment describes. Skip; not reported.
- **Partially satisfied** — the diff covers part of the requirement (e.g. handles the happy path but ignores an explicitly stated edge case). Report as Critical.
- **Missing** — no code in the diff implements the requirement. Report as Critical.
- **Divergent** — the diff implements behavior that contradicts the requirement (wrong field, wrong status, opposite condition). Report as Critical.

Do **not** report stylistic / architectural / test-coverage concerns even if you notice them — those belong in `@skills/code-review/SKILL.md` and `@skills/security-review/SKILL.md`.

### 5. Return the report to the caller

> **Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`):** the loop iterations call this skill with "do not publish; return findings as in-memory markdown for this loop iteration only" — which is now the **only** mode this skill ever operates in. The skill never publishes anywhere itself; every caller (loop iteration or final consolidating publish) receives the same in-memory return. The loop convergence math still counts Critical gaps from the returned block.

- Build the **Assignment Compliance** markdown block using the template in **Output Format** below **only when at least one Critical gap exists**. Use GitHub-flavoured Markdown by default; convert to **JIRA Wiki Markup** per `@rules/jira/general.mdc` when the calling CR wrapper signals a JIRA tracker target (`h2.` / `h3.` headings, `*bold*`, `_italic_`, `{{inline}}`, `{code:php}…{code}`, `* / # bullets`, `[label|url]`, `{quote}`).
- **Do not call `gh issue comment`, `acli`, the GitHub MCP server's `add_issue_comment`, or any JIRA write endpoint.** The skill is a pure markdown producer; the calling CR wrapper (`@skills/code-review-github/SKILL.md` / `@skills/code-review-jira/SKILL.md`) embeds the returned block into the single consolidated linked-tracker comment authored by `@skills/pr-summary/SKILL.md` (see issue #498 — one comment per linked issue per CR run) **only when a block is returned**.
- When there are no Critical gaps, **do not return a block**. Return the status `no critical gaps — assignment compliance block omitted` so the CR wrapper embeds nothing on the consolidated comment and only mirrors the status into its PR comment summary line. The principle is: report only items that still need action; satisfied requirements never appear in the linked-tracker comment.
- If no linked tracker exists (`closingIssues[]` empty for GitHub PRs, or no JIRA ticket detected for JIRA-originated), return the status `no linked issue — assignment compliance skipped` instead of a block so the CR wrapper can include the status in its PR comment summary line without embedding an empty section.
- The CR wrapper skills (`code-review`, `code-review-github`, `code-review-jira`) **must not** embed the Assignment Compliance content into the **GitHub PR** comment — it belongs in the consolidated linked-tracker comment, never on the PR comment, which carries technical findings only.

## Output Format

> **Render this block only when at least one Critical gap exists.** When there are no Critical gaps, return the skip status described in step 5 instead — never emit an empty `## Assignment Compliance` heading, a "Verdict: No critical gaps" line, a "What is satisfied" list, or an "Open questions" list. Satisfied requirements and reviewer questions are out of scope by design: this skill reports only items that still need action.

Assignment Compliance comment posted to the issue tracker (Markdown shown; convert to Wiki Markup for JIRA per `@rules/jira/general.mdc`):

```markdown
## Assignment Compliance

- **Linked task:** <issue / JIRA / Bugsnag URL>
- **Pull request:** <PR URL>
- **Authors:** <@github-handle or JIRA display name of the real change author(s), comma-separated in commit order — resolved exactly as `@skills/pr-summary/SKILL.md` resolves them; never the agent / CR identity>
- **Available behind:** <optional — present only when the change is reachable only behind a test parameter (feature flag, ENV switch, query string, admin toggle, allow-listed account); name the toggle and the value required to reach it. Omit the line entirely when the change is reachable unconditionally.>
- **Verdict:** Critical gaps found: N

### Critical gaps

#### 1. <short title in everyday language>
- **What the task asked for:** <one sentence quoting or paraphrasing the requirement, with the source comment URL or "issue description">
- **What the pull request does instead:** <one sentence describing the actual behavior implied by the diff>
- **Example a tester would see:** <concrete input → expected output vs actual output, ideally taken from the example the reporter provided; when *Available behind* is set, the example must start by enabling the gating toggle>

(Repeat for every Critical gap.)
```

The block carries no file paths, line numbers, or code snippets — the linked-tracker audience is non-technical reviewers and product owners. Technical details belong on the PR. The block also carries no "satisfied" lists and no reviewer-question lists; only Critical gaps that still need action are reported here.

## Done when
- One of the following was returned to the calling CR wrapper:
  - an **Assignment Compliance** markdown block (only when at least one Critical gap exists);
  - the status `no critical gaps — assignment compliance block omitted` (when the implementation satisfies every stated requirement);
  - the status `no linked issue — assignment compliance skipped` (when no linked tracker exists).
- The skill itself did **not** publish anywhere — no `gh issue comment`, no `acli`, no GitHub / JIRA MCP write call. Publishing is exclusively the responsibility of the CR wrapper through `@skills/pr-summary/SKILL.md` as the single consolidated linked-tracker comment, and the wrapper embeds nothing when a skip status was returned.
- The GitHub PR comment produced by the calling CR skill does **not** contain an Assignment Compliance section.
- No files were created on disk — neither in the repository nor in any external directory.
- The returned block (when present) is plain language and includes a short example for every Critical gap.
- Only Critical functional / business-logic gaps are listed — no architecture / style / coverage findings, no "what is satisfied" lists, and no "open questions" lists.
