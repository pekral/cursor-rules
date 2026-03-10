---
name: code-review-github
description: "Use when performing code review for GitHub pull requests. Analyzes code changes, identifies critical and moderate issues, runs tests, and posts review comments. Reviews code quality, security, and adherence to project standards."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- NEVER CHANGE THE CODE! Generate the output only.
- All messages formatted as markdown for output.

**Steps:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- I want you to create @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md for the issue (find it by code or URL) on GitHub.
- Find the Git branch and switch to it.
- If possible, find links to the assignment and analyze it so that you understand it and can do a quality CR. Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker.
- List only critical or moderately difficult problems for me.
- If there are any, I want you to add comments to the PR about where you found these errors. If that is not possible, I want you to create a new comment on the PR with a list of errors from the CR. If you do not find any errors, write that you have done the CR but did not find any serious errors. Every text in English.
- I want you to use the console cli tool to insert the CR result into the GitHub PR as a new comment. I do not want to list "What was checked," but only the errors.
- Run the tests and let me know if the current changes meet the requirements.  If so, add a new comment to the issue with a recommendation on what to test (briefly). If the requirements are not met or you have found critical errors, just list them for me.
