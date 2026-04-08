# PR Update and Completion Rules

## Committing and pushing

- Commit all changes and push the branch.
- If no pull request exists for the current branch, create one according to @rules/git/pr.mdc rules — link it to the original issue and follow the PR description format (title in English, body in the language of the assignment).

## Updating review status in the PR

- Update the review result comment in the pull request:
  - Mark resolved points as checked items when possible, OR
  - Format resolved points as underlined text when checkbox updates are not possible.
- If you cannot update the original comment, add a new PR comment with the same resolved-point status.

## Triggering the next review cycle

After all points are addressed, trigger the next review interaction by issue tracker:
- **GitHub:** run @skills/code-review-github/SKILL.md
- **JIRA:** run @skills/code-review-jira/SKILL.md

## Completion verification

- Confirm all review points are resolved or explicitly marked as blocked with reasons.
- Ensure the PR contains clear evidence that each review remark was handled.
- Summarize what changed, what was tested, and what requires follow-up.
