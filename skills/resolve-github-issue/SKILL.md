---
name: resolve-github-issue
description: "Use when resolving a GitHub issue. Analyzes the problem, implements a safe fix or feature, validates behavior with tests, and prepares a clean pull request."
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
- You are given a GitHub issue (ID or link)
- You need to implement a bugfix or feature based on the issue description

## Required approach
- Fully analyze the issue description and all comments
- Clearly define scope before writing code
- Classify the task:
  - **Bug** → incorrect existing behavior
  - **Feature** → new behavior
- Prefer minimal, safe, and readable changes
- Keep scope limited unless related fixes are trivial and safe

## Execution
1. Fetch and analyze the GitHub issue (description, comments, context).
2. Define exact requirements and expected behavior.
3. Classify the task (bug or feature).

### If bug
4. Reproduce the issue if possible.
5. Write or update a test capturing the failure.
6. Confirm the failure before applying the fix.

### If feature
4. Design a minimal implementation aligned with project architecture.

### Continue
7. Implement the solution (fix or feature).
8. Ensure no sensitive data is exposed in error/validation messages.
9. Run tests for affected areas and confirm correctness.
10. Add or update tests to cover the new or fixed behavior.
11. Run project fixers and resolve issues for changed files.

## Code quality and review
- Run `@skills/code-review/SKILL.md`
- Run `@skills/security-review/SKILL.md`
- Fix all critical and moderate findings

## Pull request
- Create a branch and commit changes following `@rules/git/general.mdc`
- Create a pull request with:
  - clear description of the change
  - reference to the GitHub issue
  - testing instructions

## Done when
- The issue is fully addressed
- Behavior is correct and stable
- Tests cover affected logic and pass
- No sensitive data is exposed
- Code review and security review findings are resolved
- A clean pull request is created
