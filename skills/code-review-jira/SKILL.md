---
name: code-review-jira
description: "Performs code review for JIRA-linked pull requests. Analyzes PRs, identifies critical and moderate issues, runs tests, and posts review comments to GitHub. Use when the task originates from JIRA and the user wants a CR on the corresponding GitHub PR. Do not use for GitHub-only PRs (use code-review-github) or for implementing fixes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Use the same language as the assignment.
- Do not change code; produce review output only.
- Format all output as markdown.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Apply the code-review and security-review skills for the issue (find it by code or URL on JIRA; use the acli console tool).
3. Find the Git branch for the PR and switch to it (pull latest changes if needed).
4. Fetch the bug from JIRA (by issue ID or link). Use the acli tool or MCP to get all needed information; load and analyze any other relevant resources.
5. Find links to the assignment and analyze it for a quality review. Find and analyze attachments using MCP or CLI for the issue tracker.
6. List only critical or moderately difficult problems.
7. If such problems exist, add comments on the PR at the relevant locations. If that is not possible, add a new comment on the PR with the list of errors from the CR. If no errors are found, state that the CR was done and no serious errors were found. Use English for all text.
8. Do not put raw technical CR text into the JIRA issue; adapt the text so project managers and testers can understand it.
9. Use the console CLI tool to post the CR result as a new comment on the GitHub PR. Do not list “What was checked”; list only the errors.
10. Run tests and report whether the current changes meet the requirements. If they do, add a comment to the issue with a brief testing recommendation. If not or if critical errors exist, list them.
