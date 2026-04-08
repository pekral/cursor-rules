# PR Creation Checklist

## Mandatory Rule

Pull request creation is **mandatory** for every resolved GitHub issue. Do not finish without a PR URL.

## Pre-PR Checklist

Before creating a PR, verify all of the following:

- [ ] All issue tasks are resolved
- [ ] Code follows `@skills/class-refactoring/SKILL.md` patterns
- [ ] Code review cycle is clean (no Critical or Moderate findings)
- [ ] All project fixers/linters have been run and pass
- [ ] CI checks pass (only run tests for current changes, never the entire codebase)
- [ ] Test coverage is 100% for current changes
- [ ] Tests for coverage improvements are in a separate commit

## Issue Linking

- If there is no link to the issue tracker, add a link to the issue tracker entry in the CR summary
- If possible, link it directly according to the issue tracker recommendations
- Always include an HTTP link

## PR Creation

- Create the PR according to `@rules/pr.mdc` rules
- Push the branch and create the PR automatically — do not wait for additional confirmation

## Post-PR Actions

1. Post a comment with testing recommendations into the GitHub issue
2. Include brief testing recommendations with direct in-app links (full URLs) for each recommendation so testers can click through immediately
3. Only post if the requirements are met; if not, list the critical errors instead

## Branch Cleanup

- After completion, switch back to the main git branch
