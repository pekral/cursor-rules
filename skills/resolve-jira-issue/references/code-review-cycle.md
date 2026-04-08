# Code Review Cycle

## Pre-PR code review

Before creating a PR, run `@skills/code-review-jira/SKILL.md` for the current changes. This is a mandatory CR step for the JIRA flow.

## Fix cycle

1. Run `@skills/code-review-jira/SKILL.md` on current changes.
2. Fix all **Critical** and **Moderate** findings directly in code/tests.
3. Run `@skills/code-review-jira/SKILL.md` again.
4. Repeat the CR + fix cycle until there are no Critical or Moderate findings left.

## Post-PR validation

After creating the PR, run one final validation pass with `@skills/code-review-jira/SKILL.md` to confirm no new Critical or Moderate findings were introduced.

## GitHub review comments

- Post a comment on the pull request regarding the core review on GitHub.
- Only post **critical** or **moderate** severity issues, ideally including the affected lines of code.
- If there are no critical or moderate issues, do not post anything.
- If possible, mark the issue with the label "ready for review".

## JIRA issue link

- If there is no link to the issue tracker, add a link to the issue tracker entry to the CR summary.
- If possible, link it directly according to the issue tracker recommendations.
- Always include an HTTP link.
