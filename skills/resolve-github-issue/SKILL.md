---
name: resolve-github-issue
description: "Resolves GitHub issues by fixing bugs, refactoring code, performing code and security reviews, ensuring 100% test coverage, running CI checks, and creating pull requests. Use when the user provides a GitHub issue ID or link and wants it fixed end-to-end. Do not use for JIRA, Bugsnag, or merge-only workflows."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Use the same language as the assignment.
- If not on the main git branch, switch to it.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Fetch the bug from GitHub (by issue ID or link). Use MCP or CLI to get all necessary information; load and analyze any other relevant resources.
3. Resolve the issue: generated code must follow the class-refactoring skill. Then review code per the code-review and security-review skills for current changes. If critical issues are found, fix them and repeat the review until the bug is fixed.
4. Find and analyze attachments for the assignment using MCP or CLI for the issue tracker.
5. For all changes in the current branch, analyze code coverage and ensure 100% coverage. Add any missing tests.
6. If the project has automatic fixers (e.g. Phing or composer scripts), run them and fix errors. Run CI/local checkers (run tests only for current changes, not the full codebase). Fix errors, re-run fixers, and repeat until clean. Do not format PHP code manually outside these fixers.
7. If everything is OK, create a pull request per the pr.mdc rules.
8. If there is no link to the issue tracker in the PR, add the issue tracker entry link to the CR summary (HTTP link).
9. Post a comment on the PR with the code review: only critical or moderately serious issues, ideally with affected lines. If none, do not post. If possible, add the label ready for review.
10. Run tests and report whether the changes meet requirements. If they do, add a comment to the issue with a brief testing recommendation. If not or if critical errors exist, list them.
11. Add any missing tests for current changes to reach 100% coverage; fix DRY and simplify the codebase. Put these changes in a separate commit.
12. After creating the PR, perform a code review per the code-review-github skill.
13. After finishing and pushing, perform a code review per the code-review-github skill.

**After completing the tasks**
- Once work is finished and changes are pushed to the PR, perform a code review per the code-review-github skill.
