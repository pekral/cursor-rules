---
name: code-review-github
description: "Use when performing code review for GitHub pull requests. Analyzes code changes, identifies critical and moderate issues, runs tests, and posts review comments. Reviews code quality, security, and adherence to project standards."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Switch to the main branch and make sure you have the updated main branch. Then switch to the branch where the PR is and, to be on the safe side, update the branch for the PR as well, then continue with the code review.
- I want the texts to be in the language in which the task was assigned. Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- All comments or outputs posted to GitHub (issues, pull requests, review comments, and PR descriptions) must be written in English.
- Always load existing CR reports/comments in the PR and related issue before generating a new CR report, and never repeat a previously reported finding.
- NEVER CHANGE THE CODE! Generate the output only.
- All messages formatted as markdown for output.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.

**Steps:**
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- Switch locally to the branch in PR and perform code review over changes locally on the filesystem.
- Before writing findings, collect prior review comments/reports from the PR timeline and related issue discussion. Build a dedup list by problem signature (file/scope + root cause + risk) and skip findings already reported unless severity/impact changed.
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- Always apply @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md. If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed code), also apply @.cursor/skills/mysql-problem-solver/SKILL.md for those parts; otherwise do not use the SQL skill. Find the issue by code or URL on GitHub.
- **All business logic is allowed only in classes that follow the action pattern!**
- **Action pattern (only when `vendor/pekral/arch-app-services` exists):** Apply @.cursor/skills/refactor-entry-point-to-action/SKILL.md rules when reviewing PHP entry points (controllers, jobs, commands, listeners). Flag violations as **Critical** in the CR report.
- **Data Validator pattern (only when `vendor/pekral/arch-app-services` exists):** If an Action throws `ValidationException` directly or calls `Validator::make()` inline, flag it as **Critical**. Validation must be delegated to a Data Validator class in `app/DataValidators/{Domain}/`.
- Find the Git branch and switch to it.
- If possible, find links to the assignment and analyze it so that you understand it and can do a quality CR. Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker. If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- List findings using exactly three severity levels: **Critical**, **Moderate**, **Minor**.
- If there are any, I want you to add comments to the PR about where you found these errors. If that is not possible, I want you to create a new comment on the PR with a list of errors from the CR. If you do not find any errors, write that you have done the CR but did not find any serious errors. Every text in English.
- I want you to use the console cli tool to insert the CR result into the GitHub PR as a new comment. Format the PR comment as: (1) One-line summary by severity (e.g. "Summary: 2 Critical, 1 Moderate"); (2) List of findings grouped by severity, each with file/line (or file) and a short, actionable recommendation. Do not list "What was checked," only the findings.
- Use readable Markdown with clear section separators and include short code suggestions for simple fixes when helpful.
- Run the tests and let me know if the current changes meet the requirements. If so, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If the requirements are not met or you have found critical errors, just list them for me.
- If is needed use interactive-testing skill for testing

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
- Based on the discussion in the assignment, is the proposed solution to the problems safe and effective? Analyze the assignment and all discussions related to this task and write me your conclusion!
