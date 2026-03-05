---
name: resolve-jira-issue
description: "Resolves JIRA issues by fixing bugs, refactoring code, performing code and security reviews, ensuring 100% test coverage, running CI checks, and creating pull requests. Links PRs to JIRA and updates issue status. Use when the user provides a JIRA issue ID or link. Do not use for GitHub-only issues, Bugsnag, or merge-only workflows."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Use the same language as the assignment.
- If not on the main git branch, switch to it before resolving.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Fetch the bug from JIRA (by issue ID or link). Use the acli tool or MCP to get all needed information; load and analyze any other relevant resources.
3. Resolve the issue: generated code must follow the class-refactoring skill. Then review code per the code-review and security-review skills for current changes. If critical issues are found, fix them and repeat the review until the bug is fixed.
4. Find and analyze attachments for the assignment using MCP or CLI for the issue tracker.
5. For all changes in the current branch, analyze code coverage and ensure 100% coverage. Add any missing tests.
6. If the project has automatic fixers (e.g. Phing or composer scripts), run them and fix errors. Run CI/local checkers (run tests only for current changes). Fix errors, re-run fixers, and repeat until clean. Do not format PHP code manually outside these fixers.
7. If everything is OK, create a pull request per the pr.mdc rules.
8. If there is no link to the issue tracker in the PR, add the issue tracker entry link to the CR summary (HTTP link).
9. Post a comment on the PR with the code review: only critical or medium-severity issues, ideally with affected lines. If none, do not post. If possible, mark the issue ready for review.
10. After completing all GitHub tasks, link the created PR in the JIRA issue and set the JIRA issue status to ready for review.
11. Run tests and report whether the changes meet requirements. If they do, add a comment to the issue with a brief testing recommendation. If not or if critical errors exist, list them.
12. Add any missing tests for current changes to reach 100% coverage; fix DRY and simplify the codebase. Put these changes in a separate commit.
13. Post a comment on the PR with the code review: only critical or moderately serious issues, ideally with affected lines. If none, do not post. If possible, add the label ready for review.
14. After finishing and pushing to the PR, perform a code review per the code-review-jira skill.

**After completing the tasks**
- Once work is finished and changes are pushed to the PR, perform a code review per the code-review-jira skill.
