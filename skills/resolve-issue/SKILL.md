---
name: resolve-issue
description: "Use when resolving an issue from any supported tracker (GitHub, JIRA, Bugsnag). Detects the source automatically from the provided link or ID, implements a safe fix or feature, validates with tests, and creates a pull request."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Follow project architecture and testing rules
- Do not expose sensitive/internal details in user-facing messages
- Preserve existing behavior unless explicitly required otherwise

## Use when
- You are given an issue link, URL, or ID from any supported tracker
- You need to implement a bugfix or feature based on the issue

## Source detection

See `references/source-detection.md` for the detection table and rules.

## Preparation

Before starting the resolution flow:
- Switch to the `main` branch and pull the latest changes so the working tree reflects the current state of the repository before creating the feature branch.

## Required approach
- Fully analyze the issue (description, comments, attachments)
- Clearly define scope before writing code
- Classify the task:
  - **Bug** — incorrect existing behavior or runtime error
  - **Feature** — new behavior
- Prefer minimal, safe, and readable changes
- Keep scope limited unless related fixes are trivial and safe
- When implementing DB work, prefer batch operations over per-row queries inside loops per `@rules/sql/optimalize.mdc` "Batch over per-row operations" — ModelManager `batchUpdate` / `batchInsert`, `whereIn(...)->delete()`, or a single bulk read keyed in memory. Per-row queries are allowed only when iterations have an unavoidable side-effect dependency that is justified in a code comment.

## Execution

1. Verify the issue belongs to the current project before proceeding:
   - **GitHub:** the issue repository must match the current Git remote origin.
   - **JIRA:** the issue project key must match the configured JIRA project for this repository.
   - If the issue does not belong to the current project, refuse to process it and inform the user.
2. Fetch and analyze the issue from the detected source by running the deterministic loader for that tracker — never call `gh`, `acli`, or REST endpoints directly. Read all required fields off the resulting JSON document.
   - **GitHub:** `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>`. If the script is unavailable (missing tool, exit code 2/3), fall back to the GitHub MCP server.
   - **JIRA:** `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>`. If the script is unavailable (missing tool, exit code 2/3), fall back to the JIRA MCP server.
3. Define exact requirements and expected behavior.
4. Classify the task (bug or feature).

### Comment analysis

5. Before analyzing the problem, fetch and read **all comments and replies** from the issue tracker (GitHub, JIRA, or Bugsnag). For GitHub and JIRA issues, read `comments[]` directly off the JSON loaded in step 2 — do not issue a second listing call:
   - Group comments by conversation thread (e.g., review threads, reply chains).
   - For each thread, determine:
     - **Current requirements** — requests or conditions that are still valid and unfulfilled.
     - **Resolved items** — requirements already addressed by merged PRs or subsequent comments.
     - **Outdated items** — requests superseded by newer comments or decisions.
   - Use only the **current requirements** (combined with the issue description) as input for the next step.

### Problem analysis

6. Run `@skills/analyze-problem/SKILL.md` using the issue description, current requirements from comment analysis, and any available context as input.
7. Review the analysis output and split the identified items into two groups:
   - **In scope** — items that directly match the issue requirements. These will be implemented.
   - **Out of scope** — items that are valid findings but fall outside the current issue. These will be added to the PR summary as a TODO list for future tasks.

### Phase planning (commit plan)

Before writing any code, decide how the in-scope work will be split into commits within the PR.

1. **Detect existing phases** in the issue description and the kept comments. Phase markers include explicit headings such as `Phase 1`, numbered milestones, ordered acceptance-criteria blocks, or a step-by-step plan written by the reporter.
2. **If phases exist:** treat each phase as exactly **one commit**. Keep the original phase order as commit order. Do not merge, reorder, or re-scope phases.
3. **If no phases exist but the assignment is long or covers multiple distinct concerns:** propose a phased breakdown — each phase must be independently reviewable and yield a working state — then map **one phase per commit**.
4. **If the assignment is small and atomic:** keep it as a single commit. Do not invent artificial phases.
5. Record the planned phases as a numbered list (one line per commit, with the intended commit message in `type(scope): description` form per `@rules/git/general.mdc`) **before** starting implementation. This list is the commit plan for step 11.
6. During implementation, commit at the end of each phase. Run pre-push fixers and tests on the changes belonging to that phase before moving on.

### If bug
8. Reproduce the issue if possible.
9. Write or update a test capturing the failure.
10. Confirm the failure before applying the fix.

### If feature
8. Design a minimal implementation aligned with project architecture.

### Continue
11. Implement the solution for all **in-scope** items identified in step 7.
12. Ensure no sensitive data is exposed in error/validation messages.
13. If the implementation introduced new database migrations, run them (`php artisan migrate` for Laravel projects, or the project-specific equivalent) before executing the affected tests or creating the pull request.
14. Run tests for affected areas and confirm correctness.
15. Add or update tests to cover the new or fixed behavior.
16. Verify 100% code coverage for all changed or added code paths — if coverage tooling exists, run it and confirm the result before proceeding.

## Pre-push quality gates

Follow the workflow defined in `references/quality-gates.md`.

## Code quality and review loop

After implementation and pre-push quality gates pass, and **before creating the pull request**, run the review loop on the local changes:

1. Run `@skills/code-review/SKILL.md`
2. If **Critical** or **Moderate** findings exist:
   - Apply the **Suggested Fix** snippet from each finding directly to the working tree
   - Add or update a reproducer test for each finding using its **Faulty Example**, **Expected Behavior**, and **Test Hint**
   - Re-run the pre-push quality gates on touched files
   - Repeat from step 1
3. Iterate until no **Critical** or **Moderate** findings remain

PR-comment processing via `@skills/process-code-review/SKILL.md` remains the path used **after** a PR exists; it is not part of this pre-PR loop because it requires an open PR to operate on.

## Testing

After the code review loop passes clean, and **still before creating the pull request**, validate the change:

1. Run `@skills/security-review/SKILL.md`
2. Run `@skills/test-like-human/SKILL.md`

Resolve any **Critical** or **Moderate** findings from these skills before continuing. If a finding requires code changes, re-run the **Code quality and review loop** to re-validate.

## Pull request

Once review and testing are clean:

- Create a branch and commit changes following `@rules/git/general.mdc`
- Create a pull request with:
  - clear description of the change
  - reference to the original issue
  - testing instructions
  - **Summary** — concise overview of what changed and why
  - **TODO list** — if any **out-of-scope** items were identified in step 7, include them in the PR summary under a `## TODO` section as a checklist of potential follow-up tasks

## Final report

Reporting is split by audience and destination:

### Technical report → codebase tracker (GitHub PR)

Post the technical report as a comment on the GitHub PR, since that is where the codebase and testing state live. It must contain:

- **Code review summary** — outcome of `@skills/code-review/SKILL.md` (findings addressed during the loop and the final clean state)
- **Security review summary** — outcome of `@skills/security-review/SKILL.md`
- **Testing notes** — outcome of `@skills/test-like-human/SKILL.md`, including scenarios exercised and any caveats

### Non-technical report → original task tracker

Post the non-technical report on the issue tracker where the task with the assignment was created (the original tracker, regardless of where the PR lives):

- **GitHub** (task filed as a GitHub issue): post as a comment on the original issue
- **JIRA** (task filed in JIRA): post as a JIRA comment formatted with JIRA Wiki Markup per `@rules/jira/general.mdc` (no Markdown headings, fenced code blocks, or tables)
- **Bugsnag** (task originated from a Bugsnag error): post as a comment on the linked GitHub issue (if available)

The non-technical report must be understandable by non-technical testers and product managers and contain:

- **What changed:** a brief, plain-language summary of the fix or feature
- **How to test:** step-by-step instructions a tester can follow to verify the change works correctly
- **Risk areas and edge cases:** specific scenarios the tester should focus on to catch potential regressions or unexpected behavior

### GitHub-specific follow-up
- If the original repository uses a `ready for review` (or equivalent) label, apply it to the source issue once the PR is open to signal it is ready for reviewers. Skip this step when the project does not use such labels.

### JIRA-specific follow-up
- Link the created PR back to the JIRA issue.
- Do not change the JIRA issue status — per `@rules/jira/general.mdc`, status transitions are handled by humans only.

## References

- references/source-detection.md
- references/quality-gates.md

## Done when
- The issue is fully addressed
- Behavior is correct and stable
- Tests cover affected logic with 100% coverage and pass
- Pre-push fixers and checkers ran clean on all changed files
- No sensitive data is exposed
- Code review loop passed with no Critical or Moderate findings **before the PR was created**
- Security review completed **before the PR was created**
- Test-like-human completed **before the PR was created**
- A clean pull request is created with a summary
- Technical report posted on the GitHub PR
- Non-technical report posted on the original issue tracker
- For JIRA issues: PR is linked back and a summary comment is posted

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
