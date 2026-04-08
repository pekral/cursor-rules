# PR Creation Policy

## Mandatory PR

Pull request creation is mandatory for every resolved JIRA issue. Do not finish without a PR URL.

## When to create

Only after the CR cycle is clean (no Critical or Moderate findings), automatically push the branch and create a GitHub pull request according to the `pr.mdc` rules. This step is mandatory — do not wait for additional confirmation.

## Post-PR actions

1. Link the created PR in the JIRA issue.
2. Change the status of the JIRA issue to "ready for review".
3. Post a JIRA comment with brief testing recommendations and direct in-app links (full URLs) per `references/jira-comment-formatting.md`.

## Post-PR testing

- Run tests and verify the current changes meet the requirements.
- If requirements are met, add a new comment to the JIRA issue with testing recommendations.
- If requirements are not met or critical errors are found, list them for the user.

## Post-PR code review

- Once work is pushed, perform a code review according to `@skills/code-review-jira/SKILL.md`.
- If according to `@skills/test-like-human/SKILL.md` the changes can be tested, do it.
- Run `@skills/code-review-jira/SKILL.md` for the current issue as a final pass.
