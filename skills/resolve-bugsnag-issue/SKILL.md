---
name: resolve-bugsnag-issue
description: "Use when resolving a Bugsnag issue. Reproduces the error, applies a safe fix, validates behavior with tests, and prepares a clean pull request."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Follow project architecture and testing rules
- Do not expose sensitive/internal details in user-facing error messages
- Preserve existing behavior unless explicitly required otherwise

## Use when
- You are given a Bugsnag issue ID or link
- You need to reproduce and fix a runtime error or exception

## Required approach
- Treat all Bugsnag issues as real runtime failures
- Prefer TDD when the issue can be reliably reproduced
- Focus on minimal, safe fixes with clear intent
- Ensure changes are covered by tests
- Keep scope limited to the issue unless related fixes are trivial and safe

## Execution
1. Fetch and analyze the Bugsnag issue (stack trace, context, metadata).
2. Analyze related code and all issue comments to define the exact scope.
3. Identify a reproducible failure scenario.
4. Write or update a test that captures the failure (if feasible).
5. Confirm the failure (test should fail before fix).
6. Implement the minimal fix required to resolve the issue.
7. Ensure no sensitive information is leaked in error or validation messages.
8. Run tests for affected areas and confirm behavior is correct.
9. Add or update tests to cover the fixed logic and key edge cases.
10. Run project fixers and resolve issues for changed files.

## Code quality and review
- Run `@skills/code-review/SKILL.md` for the current changes
- Run `@skills/security-review/SKILL.md`
- Fix all critical and moderate findings before proceeding

## Pull request
- Create a branch and commit changes following `@rules/git/general.mdc`
- Create a pull request with:
  - clear description of the issue and fix
  - reference to the Bugsnag issue (link or ID)
  - testing notes (how to verify the fix)

## Done when
- The issue is reproducible or clearly understood
- The fix resolves the root cause (not only symptoms)
- Tests cover the affected behavior and pass
- No sensitive data is exposed in outputs
- Code review and security review findings are resolved
- A clean pull request is created
