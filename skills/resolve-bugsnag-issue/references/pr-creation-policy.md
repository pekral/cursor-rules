# PR Creation Policy

## Mandatory PR

Pull request creation is mandatory for every resolved Bugsnag issue. After checks pass, automatically push the branch and create a GitHub PR. Do not finish without a PR URL.

## PR Creation Rules

- Create the PR according to `pr.mdc` rules.
- This step is mandatory — do not wait for additional confirmation.
- Only create the PR after the code review cycle is clean (no Critical or Moderate findings).

## Issue Tracker Linking

- If there is no link to the issue tracker, add a link to the issue tracker entry to the CR summary.
- If possible, link it directly according to the issue tracker recommendations.
- Always include an HTTP link.

## Post-PR Steps

1. **Testing recommendations** — Run the tests and verify the current changes meet the requirements. If they do, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If requirements are not met or critical errors exist, list them.
2. **Human testing** — If according to `@skills/test-like-human/SKILL.md` the changes can be tested, do it.
3. **Final code review** — Run `@skills/code-review-github/SKILL.md` for the current issue.
4. **Code quality commit** — Write missing tests for current changes and ensure 100% coverage, fix DRY violations, and simplify the code base for readability. These changes go in a separate commit.
5. **Branch cleanup** — After all tasks are complete, switch back to the main git branch.
