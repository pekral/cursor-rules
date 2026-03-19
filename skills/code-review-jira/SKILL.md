---
name: code-review-jira
description: "Use when performing code review for JIRA issues. Analyzes pull requests, identifies critical and moderate issues, runs tests, and posts review comments to GitHub PRs. Reviews code quality, security, and adherence to project standards."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Switch to the main branch and make sure you have the updated main branch. Then switch to the branch where the PR is and, to be on the safe side, update the branch for the PR as well, then continue with the code review.
- I want the texts to be in the language in which the task was assigned. Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- NEVER CHANGE THE CODE! Generate the output only.
- All messages formatted as markdown for output.
- For comments posted to JIRA, always use JIRA Wiki Markup (not Markdown). Never use fenced code blocks (```), markdown headings (#), or markdown tables.

**Universal JIRA Comment Formatting**
- Use this output shape for every JIRA update from this skill:
- `h3. <short status title>`
- One short business-readable paragraph (non-technical wording)
- `h4. Findings summary` and bullet list using `*`
- If needed, `h4. Testing recommendations` and bullet list using `*`
- Inline references with `{{...}}` (ticket id, endpoint, env, status code)
- Use `{code}` / `{code:json}` only for short examples; never include markdown fences in JIRA comments
- Keep comments understandable for project managers and testers, not only developers

**Steps:**
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- First, find the open pull requests that are automatically linked to JIRA via the branch name. If you can’t find a PR by the branch name, review all the comments in the issue and locate the relevant PR.  Always check only the open pull requests and ignore the rest!
- Switch locally to the branch in PR and perform code review over changes locally on the filesystem.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
- Always apply @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md. If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed code), also apply @.cursor/skills/mysql-problem-solver/SKILL.md for those parts; otherwise do not use the SQL skill. Find the issue by code or URL on JIRA (use acli console tool).
- Find the Git branch and switch to it (if needed pull the latest changes).
- I want you to fix the bug from JIRA (you have either the ID or a link to JIRA). Use the acli tool or MCP server to get all the information you need about the bug so you can fix it. If you have other resources available that you could use to understand the problem, load them and analyze them.
- If possible, find links to the assignment and analyze it so that you understand it and can do a quality CR. Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker. If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- List findings using exactly three severity levels: **Critical**, **Moderate**, **Minor**.
- If there are any, I want you to add comments to the PR about where you found these errors. If that is not possible, I want you to create a new comment on the PR with a list of errors from the CR. If you do not find any errors, write that you have done the CR but did not find any serious errors. Every text in English.
- I don't want to enter technical data into the JIRA issue tracker after code review, but I want to edit the text so that project managers and testers can understand it.
- I want you to use the console cli tool to insert the CR result into the GitHub PR as a new comment. Format the PR comment as: (1) One-line summary by severity (e.g. "Summary: 2 Critical, 1 Moderate"); (2) List of findings grouped by severity, each with file/line (or file) and a short, actionable recommendation. Do not list "What was checked," only the findings.
- Run the tests and let me know if the current changes meet the requirements.  If so, add a new comment to the issue with a recommendation on what to test (briefly). If the requirements are not met or you have found critical errors, just list them for me.
- If is needed use interactive-testing skill for testing

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
- Based on the discussion in the assignment, is the proposed solution to the problems safe and effective? Analyze the assignment and all discussions related to this task and write me your conclusion!
