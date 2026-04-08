# Code Review Cycle

## Review Skills

Use the following skills for review during the resolution process:

- `@skills/code-review/SKILL.md` — general code review
- `@skills/security-review/SKILL.md` — security review
- `@skills/code-review-github/SKILL.md` — GitHub-specific code review (mandatory CR before PR)

## Pre-PR Review Cycle

Before creating a PR, run `@skills/code-review-github/SKILL.md` for the current changes. This is a mandatory code review.

### Iteration rules

1. Fix all **Critical** and **Moderate** findings directly in code/tests.
2. Run `@skills/code-review-github/SKILL.md` again.
3. Repeat the CR + fix cycle until there are **no Critical or Moderate findings** left.

Only proceed to PR creation when the CR cycle is clean.

## Post-PR Review

After creating the PR, perform a final validation pass with `@skills/code-review-github/SKILL.md` for the current task.

## PR Comment Policy

Post a comment into the pull request on GitHub regarding the code review:
- Only post **critical or moderately serious** issues
- Include the lines of code that are affected
- If there are no critical or moderate issues, **do not post anything**
- If possible, mark the issue with the label `ready for review`

## Post-Completion Review

Once all work is pushed to the PR:
1. Perform a final code review with `@skills/code-review-github/SKILL.md`
2. If the changes can be tested per `@skills/test-like-human/SKILL.md`, run that skill
3. Run `@skills/code-review-github/SKILL.md` one final time for the current issue
