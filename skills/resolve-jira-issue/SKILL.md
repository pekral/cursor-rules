---
name: resolve-jira-issue
description: "Use when resolving a JIRA issue. Analyze the task, implement a safe fix or feature, validate behavior with tests, create a pull request, and link it back to JIRA."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Apply `@rules/jira/general.mdc`
- Follow project architecture and testing rules
- Do not expose sensitive/internal details in user-facing messages
- Preserve existing behavior unless explicitly required otherwise

## Use when
- You are given a JIRA issue ID or link
- You need to implement a bugfix or feature based on the issue description, comments, or attachments

## Required approach
- Analyze the JIRA issue, comments, and attachments before writing code
- Clearly define the scope from the ticket and discussion
- Classify the task:
  - **Bug** → incorrect existing behavior
  - **Feature** → new behavior
- Prefer minimal, safe, and readable changes
- Keep scope limited unless related fixes are trivial and safe

## Execution
1. Fetch and analyze the JIRA issue, comments, and relevant attachments.
2. Define exact requirements and expected behavior.
3. Classify the task as bug or feature.

### If bug
4. Reproduce the issue if possible.
5. Write or update a test that captures the failure.
6. Confirm the failure before applying the fix.

### If feature
4. Design a minimal implementation aligned with project architecture.

### Continue
7. Implement the solution.
8. Ensure no sensitive information is exposed in user-facing messages.
9. Run tests for affected areas and confirm correctness.
10. Add or update tests to cover affected behavior and important edge cases.
11. Run project fixers/checks for changed files and resolve issues.

## Code quality and review
- Run `@skills/code-review/SKILL.md`
- Run `@skills/security-review/SKILL.md`
- Fix all critical and moderate findings before proceeding

## Pull request and JIRA follow-up
- Create a branch and commit changes following `@rules/git/general.mdc`
- Create a pull request with:
  - clear description of the change
  - reference to the JIRA issue
  - testing instructions
- Link the created PR back to the JIRA issue
- Add a concise JIRA comment with implementation summary and testing recommendations using JIRA formatting rules

## Done when
- The JIRA issue is fully addressed
- Behavior is correct and stable
- Tests cover affected logic and pass
- No sensitive data is exposed
- Code review and security review findings are resolved
- A clean pull request is created
- The PR is linked back to JIRA
