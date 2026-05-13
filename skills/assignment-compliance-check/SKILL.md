---
name: assignment-compliance-check
description: "Use when checking that the pull request implementation actually fulfills the business requirements stated in the linked issue or task. Reports only Critical functional gaps and writes a plain-language markdown report outside the repository so the reviewer can open it locally."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Apply `@rules/jira/general.mdc`
- Output written to the local report file must be plain language understandable by a non-technical reader. Include short examples for every Critical gap.
- Report **only Critical** functional / business-logic gaps. Do not report architecture, code style, test coverage, refactoring opportunities, or any other concern — those are owned by the other review skills.
- Never modify code. This skill is read-only.
- Do not expose secrets, internal infrastructure paths, or PII in the report.
- The report file must live **outside the project repository** so the reviewer can open it without polluting the working tree.

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

### 5. Write the local markdown report
- Resolve the report directory: `${HOME}/.cursor-rules-reports/assignment-compliance/`. Create it with `mkdir -p` if it does not exist.
- Filename: `<owner>-<repo>-pr-<number>-<YYYYMMDD-HHmmss>.md` (timestamp in UTC, e.g. `20260513-160300`). The timestamp keeps re-runs side-by-side instead of overwriting.
- Use the layout in **Output Format** below. The body must be plain language; technical jargon (file paths, line numbers, class names) is allowed only inside the **Where in the code** subsection.

### 6. Return the report path to the caller
- The CR skill that invoked this skill includes both:
  - the absolute path of the local report file (so the reviewer can open it), and
  - a condensed **Assignment Compliance** section (Critical bullets only, no examples) inside the published CR comment.
- If no Critical gaps were found, the report still gets written with a "No critical gaps identified — implementation satisfies every stated requirement" body, and the CR comment carries the same one-line statement.

## Output Format

Local report file template:

```markdown
# Assignment Compliance Check

- **Pull request:** <PR URL>
- **Linked task:** <issue/JIRA/Bugsnag URL>
- **Checked at:** <ISO-8601 UTC timestamp>
- **Verdict:** <Critical gaps found: N> / <No critical gaps>

## Why this report
A reviewer wanted to know whether this pull request actually does what the original task asked for, in plain language. Architecture, security, and code-style notes live in the other review reports — this file lists only the business-behavior gaps that would surprise a tester or a product manager reading the issue.

## Critical gaps

### 1. <short title in everyday language>
- **What the task asked for:** <one sentence quoting or paraphrasing the requirement, with the source comment URL or "issue description">
- **What the pull request does instead:** <one sentence describing the actual behavior implied by the diff>
- **Example a tester would see:** <concrete input → expected output vs actual output, ideally taken from the example the reporter provided>
- **Where in the code:** <file path(s) — kept in this subsection only, so the rest stays non-technical>

(Repeat for every Critical gap. Omit the section entirely when there are no gaps.)

## What is satisfied
- <one bullet per requirement the PR clearly meets, plain-language>

## Open questions for the reviewer
- <optional — list requirements whose status could not be determined from the diff alone, with the reason>
```

## Done when
- A markdown report file exists at `${HOME}/.cursor-rules-reports/assignment-compliance/<owner>-<repo>-pr-<number>-<timestamp>.md`.
- The file is plain language and includes a short example for every Critical gap.
- Only Critical functional / business-logic gaps are listed — no architecture / style / coverage findings.
- The absolute report path is returned to the invoking CR skill, which embeds an `Assignment Compliance` section in the published PR comment with the same Critical bullets and a link to the local report.
- When there are no Critical gaps, the report still exists and explicitly states "No critical gaps identified".
