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

Detect the issue tracker automatically from the input:

| Input pattern | Source | Extra rules |
|---|---|---|
| GitHub URL or `#123` | GitHub | Use `gh` CLI |
| JIRA URL or issue key (e.g. `PROJ-123`) | JIRA | Apply `@rules/jira/general.mdc`, use `acli` or JIRA MCP |
| Bugsnag URL or ID | Bugsnag | Treat as runtime error, prefer TDD |

If the source cannot be determined, ask the user.

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

### Problem analysis

5. Run `@skills/analyze-problem/SKILL.md` using the issue description, comments, and any available context as input.
6. Review the analysis output and split the identified items into two groups:
   - **In scope** — items that directly match the issue requirements. These will be implemented.
   - **Out of scope** — items that are valid findings but fall outside the current issue. These will be added to the PR summary as a TODO list for future tasks.

### If bug
7. Reproduce the issue if possible.
8. Write or update a test capturing the failure.
9. Confirm the failure before applying the fix.

### If feature
7. Design a minimal implementation aligned with project architecture.

### Continue
10. Implement the solution for all **in-scope** items identified in step 6.
11. Ensure no sensitive data is exposed in error/validation messages.
12. Run tests for affected areas and confirm correctness.
13. Add or update tests to cover the new or fixed behavior.
14. Verify 100% code coverage for all changed or added code paths — if coverage tooling exists, run it and confirm the result before proceeding.

## Pre-push quality gates

Before committing and pushing changes, run project fixers and checkers on changed files. Discover available tooling using this priority:

1. **Phing** — check for `build.xml` or `phing.xml` in the project root. If present, list available targets (`phing -l`) and use relevant fixer/checker targets.
2. **Composer scripts** — if Phing is not available, inspect `composer.json` `scripts` section for fixer and checker commands (e.g. `fix`, `check`, `build`, `pint-fix`, `phpcs-fix`, `rector-fix`, `pint-check`, `phpcs-check`, `rector-check`, `test:coverage`).

Run in this order:
1. **Fixers** — run all available fixers on changed files (e.g. code style, rector, normalize). Fix any issues they report.
2. **Checkers** — run all available checkers/analyzers on changed files (e.g. code style check, static analysis, audit). Resolve all reported errors before proceeding.
3. **Coverage** — if a coverage command exists, run it and confirm 100% coverage for changed code paths.

If both fixers and checkers fail or are not found, stop and inform the user.

## Pull request
- Create a branch and commit changes following `@rules/git/general.mdc`
- Create a pull request with:
  - clear description of the change
  - reference to the original issue
  - testing instructions
  - **TODO list** — if any **out-of-scope** items were identified in step 6, include them in the PR summary under a `## TODO` section as a checklist of potential follow-up tasks

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
