# Code Review Cycle

## Pre-PR Review

Before creating a PR, run `@skills/code-review-github/SKILL.md` for the current changes. This is a mandatory code review (CR).

## Iteration Rules

1. Fix all **Critical** and **Moderate** findings from the CR directly in code and tests.
2. Re-run `@skills/code-review-github/SKILL.md` after fixes.
3. Repeat the CR + fix cycle until there are no Critical or Moderate findings left.
4. Only after the CR cycle is clean, proceed to PR creation.

## Review Standards Applied

During resolution, the code must also pass:
- `@skills/code-review/SKILL.md` — general code review
- `@skills/security-review/SKILL.md` — security review for current changes
- `@skills/class-refactoring/SKILL.md` — generated code must follow refactoring standards

If any critical issues are found in new changes during these reviews, resolve them and perform further iterations until the bug is fixed.

## Post-PR Review

- After creating the PR, perform a final validation pass with `@skills/code-review-github/SKILL.md` for the current task.
- Once work is pushed, perform a code review according to `@skills/code-review-github/SKILL.md`.

## PR Comments Policy

- Post a comment into the pull request on GitHub regarding the code review.
- Only post **critical or moderately serious** issues, including the lines of code affected.
- If there are no critical or moderate issues, do not post anything.
- If possible, mark the PR with the label `ready for review`.
