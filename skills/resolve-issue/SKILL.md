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
2. Fetch and analyze the issue from the detected source.
3. Define exact requirements and expected behavior.
4. Classify the task (bug or feature).

### If bug
5. Reproduce the issue if possible.
6. Write or update a test capturing the failure.
7. Confirm the failure before applying the fix.

### If feature
5. Design a minimal implementation aligned with project architecture.

### Continue
8. Implement the solution.
9. Ensure no sensitive data is exposed in error/validation messages.
10. Run tests for affected areas and confirm correctness.
11. Add or update tests to cover the new or fixed behavior.
12. Run project fixers and resolve issues for changed files.

## Code quality and review
- Run `@skills/code-review/SKILL.md`
- Run `@skills/security-review/SKILL.md`
- Fix all critical and moderate findings before proceeding

## Pull request
- Create a branch and commit changes following `@rules/git/general.mdc`
- Create a pull request with:
  - clear description of the change
  - reference to the original issue
  - testing instructions

### JIRA-specific follow-up
- Link the created PR back to the JIRA issue
- Add a JIRA comment (using JIRA formatting rules) that is understandable by non-technical testers and product managers, containing:
  - **What changed:** a brief, plain-language summary of the fix or feature
  - **How to test:** step-by-step instructions a tester can follow to verify the change works correctly
  - **Risk areas and edge cases:** specific scenarios the tester should focus on to catch potential regressions or unexpected behavior

## Done when
- The issue is fully addressed
- Behavior is correct and stable
- Tests cover affected logic and pass
- No sensitive data is exposed
- Code review and security review findings are resolved
- A clean pull request is created
- For JIRA issues: PR is linked back and a summary comment is posted
