---
name: resolve-github-issue
description: "Use when resolving Github issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Updates GitHub issues with review results."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- If you are not on the main git branch in the project, switch to it.
- Pull request creation is mandatory for every resolved GitHub issue. After checks pass, automatically push the branch and create a GitHub PR. Do not finish without a PR URL.

**Scripts:** Use the pre-built scripts in `@skills/resolve-github-issue/scripts/` to gather data and automate repetitive tasks. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/fetch-issue.sh <ISSUE>` | Fetch full issue details: body, comments, labels, assignees |
| `scripts/run-fixers.sh` | Detect and run project-level automatic fixers (composer, phing, npm) |
| `scripts/create-pr.sh <ISSUE> <TITLE> <BODY>` | Push branch, create PR, and link it to the issue |

**References:**
- `references/task-classification.md` — bug vs feature classification rules, TDD workflow for bugs
- `references/code-review-cycle.md` — CR iteration rules, post-PR review, PR comment policy
- `references/test-coverage-policy.md` — 100% coverage requirement, testing conventions
- `references/pr-creation-checklist.md` — pre-PR checklist, issue linking, branch cleanup
- `references/action-refactoring-rules.md` — when to inline Service/Facade methods into Actions

**Examples:** See `examples/` for expected output format:
- `examples/report-issue-resolved.md` — successful resolution with PR
- `examples/report-blocked-by-review-cycle.md` — blocked by CR findings
- `examples/report-bug-tdd-flow.md` — bug resolution with TDD flow

**Steps:**
1. Run `scripts/fetch-issue.sh <ISSUE>` to load the full issue context. Analyze all comments and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
2. Find the attachments for the assignment and analyze them. Use the available MCP servers or CLI tools for the specific issue tracker. Never use a web browser.
3. Classify the task type per `references/task-classification.md` before writing any code.
4. If the task is a **bug**, follow strict TDD per `references/task-classification.md`:
   a. Write a test that reproduces the reported failure (must fail before any fix).
   b. Run the test and confirm it fails — do not proceed until you see the red failure.
   c. Implement the minimal fix that makes the test pass.
   d. Run the test again and confirm it is green.
5. If the task is a **feature**, implement it directly without the failing-test-first requirement.
6. Resolve the issue — the generated code must conform to `@skills/class-refactoring/SKILL.md`. Then review the code according to `@skills/code-review/SKILL.md` and `@skills/security-review/SKILL.md` for current changes. If you find any critical issues in the new changes, resolve them and perform further iterations of the defined code review (repeat until the bug is fixed).
7. For Action-pattern refactors during issue resolution, apply the rules in `references/action-refactoring-rules.md`.
8. For all changes in the current branch, analyze code coverage and ensure 100% coverage per `references/test-coverage-policy.md`. Apply `@rules/testing-conventions.mdc`.
9. Run `scripts/run-fixers.sh` to detect and execute project-level automatic fixers. If there are any CI (or local) checkers, run them (never run all tests for the entire codebase, only for the current changes). Fix any errors, run the fixers again, and keep fixing until all errors are fixed. Never try to format PHP code outside of these fixers yourself.
10. Before creating a PR, run the code review cycle per `references/code-review-cycle.md`:
    a. Run `@skills/code-review-github/SKILL.md` for the current changes (mandatory CR).
    b. Fix all Critical and Moderate findings directly in code/tests.
    c. Run `@skills/code-review-github/SKILL.md` again.
    d. Repeat until there are no Critical or Moderate findings left.
11. Only after the CR cycle is clean, push the branch and create a GitHub pull request per `references/pr-creation-checklist.md` and `@rules/pr.mdc`. This step is mandatory; do not wait for additional confirmation.
12. If there is no link to the issue tracker, add a link to the issue tracker entry to the CR summary and, if possible, link it directly according to the issue tracker recommendations. Be sure to include an HTTP link.
13. Post a comment into the pull request on GitHub per the PR comment policy in `references/code-review-cycle.md`. Only post critical or moderately serious issues, ideally including the lines of code that are affected. If there are none, do not post anything. If possible, mark the issue with the label `ready for review`.
14. Run the tests and verify the current changes meet the requirements. If so, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If the requirements are not met or you have found critical errors, just list them.
15. Write missing tests for current changes and ensure 100% coverage, fix DRY and simplify the code base. These changes go in a separate commit.
16. After creating the PR, perform a final validation pass with `@skills/code-review-github/SKILL.md` for the current task.
17. If you are not on the main git branch in the project, switch to it.

**After completing the tasks:**
- Once you have finished your work and pushed the changes to the PR, perform a code review per `references/code-review-cycle.md`.
- If according to `@skills/test-like-human/SKILL.md` the changes can be tested, do it.
- If the work is done, run `@skills/code-review-github/SKILL.md` for the current issue.

**Output contract:** For each resolved issue, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Issue number and title | Yes | Identifies the issue |
| Task type | Yes | `bug` or `feature` |
| Decision | Yes | `resolved` or `in progress` |
| PR | If resolved | PR number and URL |
| Tests | Yes | All passing / failing, coverage percentage |
| CR findings | Yes | Clean / count of Critical and Moderate findings |
| CI status | Yes | All passed / failed / not yet run |
| TDD steps | If bug | Red-green-refactor summary |
| Resolution summary | If resolved | Brief description of the fix or implementation |
| Testing recommendations | If resolved | Actionable links for testers |
| Blocking reasons | If blocked | Why the issue cannot be resolved or PR cannot be created |
| Confidence notes | If applicable | Caveats or assumptions (e.g., untestable path, external dependency) |
| Next action | Yes | What should happen next |
