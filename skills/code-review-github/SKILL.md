---
name: code-review-github
description: "Reviews GitHub pull requests to identify bugs, risks, and improvement opportunities. Provides clear, actionable, severity-prioritized feedback. Use when reviewing a PR, analyzing code changes, reviewing a diff, or finding issues in a pull request."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Code Review — GitHub

## Purpose

Perform senior-level code review on GitHub pull requests. Identify real issues (bugs, risks, design flaws), prioritize findings by severity, and provide actionable feedback with clear reasoning. Minimize noise — fewer high-quality comments over many weak ones.

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/review-only.mdc
- Apply @rules/github-operations.mdc
- Always apply @skills/smartest-project-addition/SKILL.md internally to identify one highest-impact, low-risk addition candidate; include it only if it maps to a real finding and keep the final output in the required findings-only format.
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- All comments or outputs posted to GitHub (issues, pull requests, review comments, and PR descriptions) must be written in English.
- Explicitly detect and report **DRY violations** (duplicated logic, duplicated validation rules, repeated branching/condition blocks, and copy-pasted code paths) in every CR result.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.

## Context Understanding

Before writing any findings, the agent MUST:

- Read the full PR diff to understand what changed.
- Read the issue description to understand the original intent and requirements.
- Understand surrounding code and architecture — not just the diff in isolation.
- Identify the purpose and scope of the PR.
- Distinguish between intentional design decisions and accidental problems.

Do not allow shallow reviews — every finding must demonstrate understanding of the change context.

## Change Analysis

For each changed file:

1. Understand what the code does before and after the change.
2. Trace callers and dependents of changed methods/classes.
3. Check whether shared logic (helpers, services, traits, base classes, interfaces) is affected.
4. Verify that all planned functionality from the issue has been implemented.

## Review Categories

Evaluate changes across these dimensions:

- **Correctness** — bugs, edge cases, incorrect logic, missing validation. See @references/common-bugs.md.
- **Safety** — security risks, data integrity, race conditions, injection vulnerabilities.
- **Performance** — inefficient queries, unnecessary computations, N+1 problems, missing indexes. See @references/performance-checks.md.
- **Maintainability** — readability, structure, naming, DRY violations, complexity.
- **Architecture** — design consistency, separation of concerns, adherence to project patterns.

## Issue Detection

**Steps:**
- **Multiple PRs per issue:** If the issue has more than one open pull request, perform a separate code review for each open PR sequentially. Review each PR independently on its own branch, post findings to the corresponding PR, and produce a per-PR summary. After all PRs are reviewed, provide a consolidated overview listing each PR with its result (clean / has findings).
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- Switch locally to the branch in PR and perform code review over changes locally on the filesystem.
- Before writing findings, collect prior review comments/reports from the PR timeline and related issue discussion. Build a dedup list by problem signature (file/scope + root cause + risk) and skip findings already reported unless severity/impact changed.
- **Plan Alignment Analysis:** Compare the implementation against the original issue description, planning documents, or step description. Identify deviations from the planned approach, architecture, or requirements. Assess whether deviations are justified improvements or problematic departures. Verify that all planned functionality has been implemented — list any missing or only partially met items.
- **Simplification analysis:** Evaluate whether the solution can be written more simply without altering the new logic, leveraging rules and conventions already defined in `rules/**/*.mdc`. Flag unnecessary complexity as a finding.
- **Regression analysis:** For every changed file, check whether the modifications could break existing functionality that is NOT part of the ticket scope. Trace callers and dependents of changed methods/classes. If a change alters shared logic (helpers, services, traits, base classes, interfaces), verify that all consumers still behave correctly. Flag any regression risk as a finding — even if the new code is correct in isolation, breaking unrelated features is **Critical**.
- Always apply @skills/code-review/SKILL.md and @skills/security-review/SKILL.md. If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed code), also apply @skills/mysql-problem-solver/SKILL.md for those parts; otherwise do not use the SQL skill. Find the issue by code or URL on GitHub.
- **Race condition review (when shared state is modified):** If the changes contain any of the following signals — read-modify-write sequences, shared counters/balances/stock/quotas, `firstOrCreate`/`updateOrCreate`, retried or re-dispatched jobs that mutate shared records, cache write-back patterns, or bulk read-then-write operations — apply @skills/race-condition-review/SKILL.md. If none of these signals are present, skip this step.
- **I/O bottleneck review (when changes touch file, storage, or external I/O):** If the changes include any of the following signals — synchronous file reads/writes on large or unbounded files, blocking HTTP calls without timeouts, storage operations executed in the request lifecycle, large file responses not streamed, or export/import operations loading all records into memory — flag each occurrence and recommend the appropriate async/streaming pattern. If none of these signals are present, skip this step.
- Apply @rules/architecture-patterns.mdc

See @references/review-guidelines.md for writing effective review comments.

## Severity Levels

Each finding MUST be assigned exactly one severity:

- **Critical** — must be fixed before merge (bugs, security vulnerabilities, data loss, broken behavior).
- **Major** — should be fixed (design flaws, regression risks, significant maintainability issues).
- **Minor** — optional improvement (naming, readability, minor DRY violations).
- **Nitpick** — style or preference (no functional impact).

Do not mix severities. Group output by severity (Critical → Major → Minor → Nitpick).

## Feedback Construction

Each finding MUST include:

1. **Location** — file and line (or file if line is not precise).
2. **What** is wrong — describe the specific problem.
3. **Why** it is a problem — explain the impact or risk.
4. **Suggested fix** — provide a concrete recommendation (include a short code snippet for simple fixes).

See @examples/review-high-signal.md for focused critical findings, @examples/review-mixed-severity.md for mixed severity output, @examples/review-clean-pr.md for minimal feedback on clean PRs.

## Forbidden Patterns

- Generic comments ("looks good", "nice work", "LGTM").
- Repeating obvious code back to the reviewer.
- Excessive nitpicking on style when tools (Pint, PHPCS) handle it.
- Commenting without reasoning — every finding needs a "why".
- Hallucinating issues that do not exist in the code.
- Reviewing: formatting, import order, lint violations, simple typos — tools cover these.

## Output Contract

```
## Summary
<One-sentence description of overall PR quality and key risk areas>

## Critical Issues
- <finding with location, description, impact, and fix>

## Major Issues
- <finding with location, description, impact, and fix>

## Minor Issues
- <finding with location, description, impact, and fix>

## Nitpicks
- <finding with location, description, and suggestion>

## Suggestions
- <proactive improvements not tied to specific problems>
```

Rules:
- Prioritize signal over quantity.
- Empty sections are allowed — do not invent findings.
- No duplication across sections.
- If there are no findings, state that no issues were found.

**Confidence notes:** If a finding relies on assumptions about runtime behavior or missing context, append a brief confidence note.

## PR Interaction

- Find the Git branch and switch to it.
- If possible, find links to the assignment and analyze it so you can do a quality CR.
- If there are any findings, add comments to the PR about where you found these errors. If that is not possible, create a new comment on the PR with the list of findings. If you do not find any issues, post a short comment stating that **no findings were identified**. Every text in English.
- Use the console CLI tool to insert the CR result into the GitHub PR as a new comment. The PR comment must contain **only findings** grouped by severity, each with file/line and a short, actionable recommendation. Do not include any summary, "what was checked", or praise.
- Use readable Markdown with clear section separators and include short code suggestions for simple fixes when helpful.
- Run the tests and let me know if the current changes meet the requirements. If so, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If the requirements are not met or you have found critical errors, just list them for me.
- If needed, use browser-based testing via available browser MCP tools.
- If all **Critical** and **Major** findings from the current CR cycle are resolved, run @skills/test-like-human/SKILL.md before closing the review flow (when the changes are testable). The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.

**Communication protocol:**
- Do not include praise/positive feedback; output must contain only findings.
- If you find significant deviations from the plan or requirements, explicitly flag them and ask for confirmation.
- If you identify issues with the original plan or requirements themselves, recommend updates.
- For implementation problems, provide clear guidance on fixes needed with code examples.

**After completing the tasks**
- Keep @skills/test-like-human/SKILL.md as a required final step only after **Critical** and **Major** findings are resolved and the changes are testable. The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.
- Based on the discussion in the assignment, is the proposed solution to the problems safe and effective? Analyze the assignment and all discussions related to this task and write me your conclusion!

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
