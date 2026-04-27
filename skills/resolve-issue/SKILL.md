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

## Required approach
- Fully analyze the issue (description, comments, attachments)
- Clearly define scope before writing code
- Classify the task:
  - **Bug** — incorrect existing behavior or runtime error
  - **Feature** — new behavior
- Prefer minimal, safe, and readable changes
- Keep scope limited unless related fixes are trivial and safe

## Execution

1. Verify the issue belongs to the current project before proceeding:
   - **GitHub:** the issue repository must match the current Git remote origin.
   - **JIRA:** the issue project key must match the configured JIRA project for this repository.
   - If the issue does not belong to the current project, refuse to process it and inform the user.
2. Fetch and analyze the issue from the detected source. For JIRA issues, use `acli` as the primary tool. If `acli` is unavailable, fall back to JIRA MCP server.
3. Define exact requirements and expected behavior.
4. Classify the task (bug or feature).

### Comment analysis

5. Before analyzing the problem, fetch and read **all comments and replies** from the issue tracker (GitHub, JIRA, or Bugsnag):
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

1. **Detect existing phases** in the issue description and the kept comments. Phase markers include explicit headings such as `Fáze 1` / `Phase 1`, numbered milestones, ordered acceptance-criteria blocks, or a step-by-step plan written by the reporter.
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
13. Run tests for affected areas and confirm correctness.
14. Add or update tests to cover the new or fixed behavior.
15. Verify 100% code coverage for all changed or added code paths — if coverage tooling exists, run it and confirm the result before proceeding.

## Pre-push quality gates

Follow the workflow defined in `references/quality-gates.md`.

## Pull request
- Create a branch and commit changes following `@rules/git/general.mdc`
- Create a pull request with:
  - clear description of the change
  - reference to the original issue
  - testing instructions
  - **TODO list** — if any **out-of-scope** items were identified in step 7, include them in the PR summary under a `## TODO` section as a checklist of potential follow-up tasks

## Code quality and review loop

After the pull request is created, run the following review loop:

1. Run `@skills/code-review/SKILL.md`
2. If **Critical** or **Moderate** findings exist:
   - Run `@skills/process-code-review/SKILL.md` to fix them
   - Repeat from step 1
3. Iterate until no **Critical** or **Moderate** findings remain

After the review loop passes clean:

4. Run `@skills/security-review/SKILL.md`
5. Run `@skills/test-like-human/SKILL.md`

## Final report

Post the final report (code review result, security review result, and test-like-human result) back to the issue tracker where the assignment originated:

- **GitHub:** post as a comment on the original issue
- **JIRA:** post as a JIRA comment (using JIRA formatting rules) understandable by non-technical testers and product managers, containing:
  - **What changed:** a brief, plain-language summary of the fix or feature
  - **How to test:** step-by-step instructions a tester can follow to verify the change works correctly
  - **Risk areas and edge cases:** specific scenarios the tester should focus on to catch potential regressions or unexpected behavior
- **Bugsnag:** post as a comment on the linked GitHub issue (if available)

### JIRA-specific follow-up
- Link the created PR back to the JIRA issue

## References

- references/source-detection.md
- references/quality-gates.md

## Done when
- The issue is fully addressed
- Behavior is correct and stable
- Tests cover affected logic with 100% coverage and pass
- Pre-push fixers and checkers ran clean on all changed files
- No sensitive data is exposed
- Code review loop passed with no Critical or Moderate findings
- Security review completed
- Test-like-human completed
- Final report posted to the issue tracker
- A clean pull request is created
- For JIRA issues: PR is linked back and a summary comment is posted
