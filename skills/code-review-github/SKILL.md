---
name: code-review-github
description: "Use when performing code review for GitHub pull requests. Analyzes code changes, identifies critical and moderate issues, runs tests, and posts review comments. Reviews code quality, security, and adherence to project standards."
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
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.

**Steps:**
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- Switch locally to the branch in PR and perform code review over changes locally on the filesystem.
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- I want you to create @.cursor/skills/code-review/SKILL.md, @.cursor/skills/security-review/SKILL.md and, when the changes involve SQL queries, repositories, migrations, or query builder code, @.cursor/skills/mysql-problem-solver/SKILL.md for the issue (find it by code or URL) on GitHub.
- Find the Git branch and switch to it.
- If possible, find links to the assignment and analyze it so that you understand it and can do a quality CR. Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker. If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- List only critical or moderately difficult problems for me.
- If there are any, I want you to add comments to the PR about where you found these errors. If that is not possible, I want you to create a new comment on the PR with a list of errors from the CR. If you do not find any errors, write that you have done the CR but did not find any serious errors. Every text in English.
- I want you to use the console cli tool to insert the CR result into the GitHub PR as a new comment. I do not want to list "What was checked," but only the errors.
- Run the tests and let me know if the current changes meet the requirements.  If so, add a new comment to the issue with a recommendation on what to test (briefly). If the requirements are not met or you have found critical errors, just list them for me.
- If is needed use interactive-browser-testing skill for testing

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
- Based on the discussion in the assignment, is the proposed solution to the problems safe and effective? Analyze the assignment and all discussions related to this task and write me your conclusion!
