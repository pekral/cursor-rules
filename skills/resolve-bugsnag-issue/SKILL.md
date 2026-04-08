---
name: resolve-bugsnag-issue
description: "Use when resolving Bugsnag issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Updates GitHub issues with review results."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- If you are not on the main git branch in the project, switch to it.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
- Pull request creation is mandatory for every resolved Bugsnag issue. After checks pass, automatically push the branch and create a GitHub PR. Do not finish without a PR URL.

**Scripts:** Use the pre-built scripts in `@skills/resolve-bugsnag-issue/scripts/` to gather data and run checks. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/fetch-bugsnag-issue.sh <ID_OR_URL>` | Fetch Bugsnag issue details (error class, stacktrace, breadcrumbs) |
| `scripts/run-project-fixers.sh [PROJECT_ROOT]` | Discover and run project-level code fixers (Phing, Composer scripts) |
| `scripts/run-changed-tests.sh [BASE_BRANCH]` | Run tests only for files changed in the current branch |

**References:**
- `references/tdd-workflow.md` — strict TDD red-green cycle, coverage requirements
- `references/code-review-cycle.md` — CR iteration rules, review standards, PR comment policy
- `references/action-pattern-refactoring.md` — when to inline Service/Facade methods into Actions
- `references/pr-creation-policy.md` — mandatory PR creation, issue tracker linking, post-PR steps
- `references/ci-and-fixers-policy.md` — automatic fixers, CI checkers, iteration cycle

**Examples:** See `examples/` for expected output format:
- `examples/report-issue-resolved.md` — successfully resolved Bugsnag issue
- `examples/report-blocked-by-cr.md` — blocked by code review findings

**Steps:**
1. Analyze all comments in the issue tracker and check what needs to be done accordingly. Stick strictly to the assignment and comments!
2. Fetch the Bugsnag issue details using `scripts/fetch-bugsnag-issue.sh` (you either got an ID or a link to Bugsnag). Use the MCP server to get all the necessary information about the bug so you can fix it. If you have other resources available that you could use to understand the problem, load them and analyze them. Use the available CLI tools or MCP servers to load them. If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
3. Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker.
4. Follow the strict TDD workflow per `references/tdd-workflow.md`:
   a. Write a test that reproduces the reported failure (the test must fail before any fix is applied).
   b. Run the test and confirm it fails — do not proceed until you see the red failure.
   c. Implement the minimal fix that makes the test pass.
   d. Run the test again and confirm it is green.
5. Resolve this issue (the generated code must be according to @skills/class-refactoring/SKILL.md), then review the code according to @skills/code-review/SKILL.md and @skills/security-review/SKILL.md for current changes. If you find any critical issues in the new changes, resolve them and perform further iterations of the defined code review (repeat until the bug is fixed).
6. For Action-pattern refactors during issue resolution, apply the rules in `references/action-pattern-refactoring.md`.
7. For all changes in the current branch, analyze code coverage and ensure that all changes are covered by tests. Use `scripts/run-changed-tests.sh` to identify and run relevant tests. Add any missing tests to ensure 100% coverage.
8. Apply @rules/testing-conventions.mdc
9. Run project fixers and CI checkers per `references/ci-and-fixers-policy.md`. Use `scripts/run-project-fixers.sh` to discover and execute them. Fix any errors, run the fixers again, and keep fixing until all errors are fixed. Never try to format PHP code outside of these fixers yourself.
10. Before creating a PR, run the code review cycle per `references/code-review-cycle.md`:
    a. Run @skills/code-review-github/SKILL.md for the current changes (mandatory CR).
    b. Fix all Critical and Moderate findings directly in code/tests.
    c. Re-run @skills/code-review-github/SKILL.md.
    d. Repeat until there are no Critical or Moderate findings left.
11. Only after the CR cycle is clean, automatically push the branch and create a GitHub pull request per `references/pr-creation-policy.md`. This step is mandatory; do not wait for additional confirmation.
12. If there is no link to the issue tracker, add a link to the issue tracker entry to the CR summary and, if possible, link it directly according to the issue tracker recommendations. Be sure to include an HTTP link.
13. Post a comment into the pull request on GitHub per the PR comment policy in `references/code-review-cycle.md`. Only post critical or moderately serious issues, including the lines of code affected. If there are none, don't post anything! If possible, mark the issue with the label ready for review.
14. Run the tests and let me know if the current changes meet the requirements. If so, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If the requirements are not met or you have found critical errors, just list them for me.
15. Write missing tests for current changes and ensure 100% coverage, fix DRY violations, and simplify the code base so that it is easy to read for humans, but also as simple as possible. These changes will be in a separate commit.
16. After creating the PR, perform a final validation pass with @skills/code-review-github/SKILL.md for the current task.
17. If you are not on the main git branch in the project, switch to it.

**After completing the tasks:**
- Once you have finished your work and pushed the changes to PR, perform a code review according to @skills/code-review-github/SKILL.md.
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
- If the work is done, run @skills/code-review-github/SKILL.md for the current issue.

**Output contract:** For each resolved Bugsnag issue, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Decision | Yes | `resolved` or `blocked` |
| Bugsnag ID | Yes | Bugsnag error identifier |
| PR | If resolved | PR number and title |
| Root cause | Yes | Brief description of what caused the error |
| TDD status | Yes | Red-green cycle completed / failed |
| Code review | Yes | Clean / N Critical, M Moderate findings |
| CI status | Yes | All passed / failed checks listed |
| Coverage | Yes | 100% of changed lines / gaps listed |
| Fix summary | If resolved | What was changed and where |
| Tests added | If resolved | List of new/modified test methods |
| Testing recommendations | If resolved | In-app URLs for manual QA |
| Blocking reasons | If blocked | Why the issue cannot be resolved yet |
| Confidence notes | If applicable | Caveats, assumptions, or risk notes |
| Next action | Yes | What should happen next |
