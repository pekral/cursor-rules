---
name: resolve-jira-issue
description: "Use when resolving JIRA issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Links PRs to JIRA issues and updates issue status."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Apply @rules/jira-operations.mdc
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- If you are not on the main git branch in the project, switch to it.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
- Pull request creation is mandatory for every resolved JIRA issue. After checks pass, automatically push the branch and create a GitHub PR, then link it back to the JIRA issue. Do not finish without a PR URL.

**Scripts:** Use the pre-built scripts in `@skills/resolve-jira-issue/scripts/` to gather data and perform actions. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/fetch-jira-issue.sh <ISSUE>` | Fetch full JIRA issue details including comments and attachments |
| `scripts/transition-jira-issue.sh <ISSUE> <STATUS>` | Transition a JIRA issue to a target status |
| `scripts/push-and-create-pr.sh <ISSUE> [BASE]` | Push the current branch and create a GitHub PR linked to the JIRA issue |

**References:**
- `references/task-classification.md` — bug vs feature classification, TDD workflow for bugs, feature workflow
- `references/jira-comment-formatting.md` — universal JIRA comment structure, wiki markup rules, inline code formatting
- `references/code-review-cycle.md` — pre-PR CR, fix cycle (Critical/Moderate), post-PR validation, GitHub review comments
- `references/test-coverage-policy.md` — 100% coverage requirement, CI/fixer rules, Action-pattern refactors
- `references/pr-creation-policy.md` — mandatory PR creation, post-PR actions, JIRA status update, final validation

**Examples:** See `examples/` for expected output format:
- `examples/report-issue-resolved.md` — successful issue resolution report
- `examples/report-blocked-by-ci.md` — resolution blocked by CI failures
- `examples/jira-comment-implementation.md` — correctly formatted JIRA implementation summary comment

**Steps:**
1. Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
2. Retrieve the JIRA issue using `scripts/fetch-jira-issue.sh <ISSUE>` or the preferred JIRA tool (see @rules/jira-operations.mdc) to get all issue details (including comments and attachments). If you have other resources available that you could use to understand the problem, load them and analyze them.
3. Classify the task type per `references/task-classification.md` before writing any code.
4. If the task is a **bug**, follow strict TDD per `references/task-classification.md`.
5. If the task is a **feature**, implement it directly without the failing-test-first requirement.
6. Resolve the issue (the generated code must follow @skills/class-refactoring/SKILL.md), then review the code according to @skills/code-review/SKILL.md and @skills/security-review/SKILL.md for current changes. If you find any critical issues in the new changes, resolve them and perform further iterations of the defined code review (repeat until the issue is fixed).
7. For Action-pattern refactors during issue resolution, follow the rules in `references/test-coverage-policy.md`.
8. Find the attachments for the assignment and analyze them. Use the available MCP servers or CLI tools for the specific issue tracker.
9. For all changes in the current branch, ensure 100% test coverage per `references/test-coverage-policy.md`.
10. Apply @rules/testing-conventions.mdc.
11. Run automatic fixers and CI checkers per `references/test-coverage-policy.md`. Fix any errors and keep iterating until all errors are resolved.
12. Run the code review cycle per `references/code-review-cycle.md`: run @skills/code-review-jira/SKILL.md, fix all Critical and Moderate findings, repeat until clean.
13. Only after the CR cycle is clean, push the branch and create a GitHub PR using `scripts/push-and-create-pr.sh <ISSUE>` or according to the pr.mdc rules. This step is mandatory — do not wait for additional confirmation.
14. Link the PR in the JIRA issue, change the JIRA status to "ready for review" using `scripts/transition-jira-issue.sh <ISSUE> "Ready for Review"`.
15. Post GitHub review comments per `references/code-review-cycle.md` — only critical or moderate severity issues with affected lines.
16. Post a JIRA comment with testing recommendations per `references/jira-comment-formatting.md`, including direct in-app links (full URLs).
17. Write missing tests for current changes in a separate commit per `references/test-coverage-policy.md`.
18. After creating the PR, run one final validation pass per `references/code-review-cycle.md`.

- **After completing the tasks**
- Once you have finished your work and pushed the changes to PR, perform a code review according to @skills/code-review-jira/SKILL.md.
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
- If the work is done, run @skills/code-review-jira/SKILL.md for the current issue.

**Output contract:** For each resolved JIRA issue, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Issue key and title | Yes | Identifies the JIRA issue |
| Task type | Yes | `bug` or `feature` |
| Decision | Yes | `resolved` or `blocked` |
| TDD status | If bug | Red-green cycle completed / not applicable |
| Test coverage | Yes | 100% on changed files / gaps listed |
| CI status | Yes | All passed / failures listed |
| Code review status | Yes | Clean / findings remaining |
| PR number and link | If resolved | GitHub PR number and URL |
| JIRA status | Yes | Current JIRA issue status |
| Blocking reasons | If blocked | Why the issue cannot be resolved |
| Confidence notes | If applicable | Caveats or assumptions |
| Next action | Yes | What should happen next |
