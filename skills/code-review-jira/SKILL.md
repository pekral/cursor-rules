---
name: code-review-jira
description: "Performs code review for JIRA issues. Analyzes pull requests, identifies critical and moderate issues, runs tests, and posts review comments to GitHub PRs. Reviews code quality, security, and adherence to project standards."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.md file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Switch to the main branch and make sure you have the updated main branch. Then switch to the branch where the PR is and, to be on the safe side, update the branch for the PR as well, then continue with the code review.
- I want the texts to be in the language in which the task was assigned. Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- NEVER CHANGE THE CODE! Generate the output only.
- All messages formatted as markdown for output.

**Steps:**
- Switch locally to the branch in PR and perform code review over changes locally on the filesystem.
- I want you to create @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md for the issue (find it by code or URL) on JIRA (use acli console tool).
- Find the Git branch and switch to it (if needed pull the latest changes).
- I want you to fix the bug from JIRA (you have either the ID or a link to JIRA). Use the acli tool or MCP server to get all the information you need about the bug so you can fix it. If you have other resources available that you could use to understand the problem, load them and analyze them.
- If possible, find links to the assignment and analyze it so that you understand it and can do a quality CR. Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker.
- List only critical or moderately difficult problems for me.
- If there are any, I want you to add comments to the PR about where you found these errors. If that is not possible, I want you to create a new comment on the PR with a list of errors from the CR. If you do not find any errors, write that you have done the CR but did not find any serious errors. Every text in English.
- I don't want to enter technical data into the JIRA issue tracker after code review, but I want to edit the text so that project managers and testers can understand it. 
- I want you to use the console cli tool to insert the CR result into the GitHub PR as a new comment. I do not want to list "What was checked," but only the errors.
- Run the tests and let me know if the current changes meet the requirements.  If so, add a new comment to the issue with a recommendation on what to test (briefly). If the requirements are not met or you have found critical errors, just list them for me.
- If is needed use interactive-browser-testing skill for testing
