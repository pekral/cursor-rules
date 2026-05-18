---
name: assignment-compliance-check
description: "Use when checking that the pull request implementation actually fulfills the business requirements stated in the linked issue or task. Reports only Critical functional gaps as a plain-language comment on the originating issue tracker — no local file is created and the report is not embedded in the PR comment."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Apply `@rules/jira/general.mdc`
- The skill **must not** write any output to disk. The report is published as a dedicated comment on the originating issue tracker (GitHub issue or JIRA ticket) — local report files, cached transcripts, and any other persisted artifacts are forbidden.
- The report **must not** be embedded into the PR comment produced by `@skills/code-review/SKILL.md`, `@skills/code-review-github/SKILL.md`, or `@skills/code-review-jira/SKILL.md`. The PR comment carries technical findings; the issue tracker comment carries assignment compliance.
- The published comment must be plain language understandable by a non-technical reader. Include a short example for every Critical gap.
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

### 5. Publish the report to the issue tracker

> **Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`):** when the calling CR skill forwards "do not publish; return findings as in-memory markdown for this loop iteration only", **skip the publishing step** — return the assembled Assignment Compliance markdown to the caller and stop. The caller still does not embed it into the PR comment; the in-memory return is used only to count compliance gaps in the loop's convergence math. Only the final (publishing) call from `process-code-review` after convergence runs publishing in full.

- Build the **Assignment Compliance** comment using the template in **Output Format** below.
- Publish it as a dedicated comment on the originating issue tracker:
  - **GitHub-originated:** post via `gh issue comment <number> --body ...` against every linked issue listed in `closingIssues[]` of the PR JSON loaded by the caller. Use GitHub-flavoured Markdown.
  - **JIRA-originated:** post via `acli` or the JIRA MCP server to the JIRA ticket. Convert the comment body to **JIRA Wiki Markup** per `@rules/jira/general.mdc` before sending (`h2.` / `h3.` headings, `*bold*`, `_italic_`, `{{inline}}`, `{code:php}…{code}`, `* / # bullets`, `[label|url]`, `{quote}`).
  - **Bugsnag-originated:** post to the linked GitHub issue using the GitHub branch above.
- When there are no Critical gaps, the comment body is the single line: *"No critical gaps identified — implementation satisfies every stated requirement."*
- If no linked issue exists (`closingIssues[]` empty for GitHub PRs, or no JIRA ticket detected for JIRA-originated), do not publish anywhere. Return the status `no linked issue — assignment compliance skipped` to the caller so the CR skill can include it in its PR comment summary line.
- If `gh issue comment` / `acli` returns a permission or network error, log the failure status `failed to publish assignment compliance on <tracker>: <reason>` and return it to the caller — do not abort the calling review. The caller surfaces the failure in the PR comment summary line.
- The CR wrapper skills (`code-review`, `code-review-github`, `code-review-jira`) **must not** embed the Assignment Compliance content into the PR comment.

## Output Format

Assignment Compliance comment posted to the issue tracker (Markdown shown; convert to Wiki Markup for JIRA per `@rules/jira/general.mdc`):

```markdown
## Assignment Compliance

- **Linked task:** <issue / JIRA / Bugsnag URL>
- **Pull request:** <PR URL>
- **Authors:** <@github-handle or JIRA display name of the real change author(s), comma-separated in commit order — resolved exactly as `@skills/pr-summary/SKILL.md` resolves them; never the agent / CR identity>
- **Available behind:** <optional — present only when the change is reachable only behind a test parameter (feature flag, ENV switch, query string, admin toggle, allow-listed account); name the toggle and the value required to reach it. Omit the line entirely when the change is reachable unconditionally.>
- **Verdict:** <Critical gaps found: N> / <No critical gaps>

### Critical gaps

#### 1. <short title in everyday language>
- **What the task asked for:** <one sentence quoting or paraphrasing the requirement, with the source comment URL or "issue description">
- **What the pull request does instead:** <one sentence describing the actual behavior implied by the diff>
- **Example a tester would see:** <concrete input → expected output vs actual output, ideally taken from the example the reporter provided; when *Available behind* is set, the example must start by enabling the gating toggle>

(Repeat for every Critical gap. Omit the entire **Critical gaps** subsection when there are none.)

### What is satisfied
- <one bullet per requirement the PR clearly meets, plain-language>

### Open questions for the reviewer
- <optional — list requirements whose status could not be determined from the diff alone, with the reason>
```

The comment carries no file paths, line numbers, or code snippets — the issue tracker audience is non-technical reviewers and product owners. Technical details belong on the PR.

## Done when
- An **Assignment Compliance** comment was published to the originating issue tracker (or the `no linked issue` / `failed to publish` status was returned to the caller when publishing was not possible).
- The PR comment produced by the calling CR skill does **not** contain an Assignment Compliance section.
- No files were created on disk — neither in the repository nor in any external directory.
- The comment is plain language and includes a short example for every Critical gap.
- Only Critical functional / business-logic gaps are listed — no architecture / style / coverage findings.
- When there are no Critical gaps, the comment is the single-line statement "No critical gaps identified — implementation satisfies every stated requirement."
