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
- All comments or outputs posted to GitHub (issues, pull requests, review comments, and PR descriptions) must be written in English.
- Always load existing CR reports/comments in the PR and related tracker discussion before generating a new CR report, and never repeat a previously reported finding.
- **Before starting the review**, analyze all comments and discussions in the issue so that you fully understand what the final state should be and what logic should have been created. Only then begin reviewing.
- NEVER CHANGE THE CODE! Generate the output only.
- All messages formatted as markdown for output.
- For comments posted to JIRA, always use JIRA Wiki Markup (not Markdown). Never use fenced code blocks (```), markdown headings (#), or markdown tables.

**Universal JIRA Comment Formatting**
- Use this output shape for every JIRA update from this skill:
- `h3. <short status title>`
- `h4. Findings` and bullet list using `*`
- If needed, `h4. Testing recommendations` and bullet list using `*`
- For every testing recommendation item, include a direct in-app link (full URL) so testers can open the exact screen immediately
- Inline references with `{{...}}` (ticket id, endpoint, env, status code)
- Use `{code}` / `{code:json}` only for short examples; never include markdown fences in JIRA comments
- Keep comments understandable for project managers and testers, not only developers

**Steps:**
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- First, find the open pull requests that are automatically linked to JIRA via the branch name. If you can’t find a PR by the branch name, review all the comments in the issue and locate the relevant PR.  Always check only the open pull requests and ignore the rest!
- Switch locally to the branch in PR and perform code review over changes locally on the filesystem.
- Before writing findings, collect prior review comments/reports from the PR timeline and JIRA discussion. Build a dedup list by problem signature (file/scope + root cause + risk) and skip findings already reported unless severity/impact changed.
- **Plan Alignment Analysis:** Compare the implementation against the original issue description, planning documents, or step description. Identify deviations from the planned approach, architecture, or requirements. Assess whether deviations are justified improvements or problematic departures. Verify that all planned functionality has been implemented — list any missing or only partially met items.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
- Always apply @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md. If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed code), also apply @.cursor/skills/mysql-problem-solver/SKILL.md for those parts; otherwise do not use the SQL skill.
  Retrieve the JIRA issue (by code or URL) using the acli console tool first. If acli is not available, use the JIRA MCP server if available. If neither is available, stop and display a message stating that at least one of these tools must be installed to use the skill.
- **I/O bottleneck review (when changes touch file, storage, or external I/O):** If the changes include any of the following signals — synchronous file reads/writes on large or unbounded files, blocking HTTP calls without timeouts, storage operations executed in the request lifecycle, large file responses not streamed, or export/import operations loading all records into memory — flag each occurrence and recommend the appropriate async/streaming pattern. If none of these signals are present, skip this step.
- **All business logic is allowed only in classes that follow the action pattern!**
- **Action pattern (only when `vendor/pekral/arch-app-services` exists):** Apply @.cursor/skills/refactor-entry-point-to-action/SKILL.md rules when reviewing PHP entry points (controllers, jobs, commands, listeners, **Livewire components**). Flag violations as **Critical** in the CR report.
- **Livewire component structure (only in Livewire projects):** Livewire components must be split into a PHP class (`app/Livewire/`) and a Blade view (`resources/views/livewire/`). Single-file (Volt) components are forbidden — flag as **Critical**. Business logic in Livewire component methods must be delegated to Action classes — flag inline business logic as **Critical**.
- **Data Validator pattern (only when `vendor/pekral/arch-app-services` exists):** If an Action throws `ValidationException` directly or calls `Validator::make()` inline, flag it as **Critical**. Validation must be delegated to a Data Validator class in `app/DataValidators/{Domain}/`.
- **BaseModelService pattern (only when `vendor/pekral/arch-app-services` exists):** All services that primarily work with a specific Eloquent Model must extend `BaseModelService` and implement `getModelManager()`, `getRepository()`, and `getModelClass()`. If a service works with a model but does not extend `BaseModelService`, flag it as **Critical**. If a service does not primarily serve a single model but exists as a plain service class, flag it as **Moderate** and recommend refactoring to an Action pattern class.
- Find the Git branch and switch to it (if needed pull the latest changes).
- I want you to fix the bug from JIRA (you have either the ID or a link to JIRA). Use the acli console tool first to retrieve all the information you need about the bug (including comments and attachments). If acli is not available, use the JIRA MCP server if available. If neither is available, stop and display a message stating that at least one of these tools must be installed to use the skill. If you have other resources available that you could use to understand the problem, load them and analyze them.
- If possible, find links to the assignment and analyze it so that you understand it and can do a quality CR. Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker. If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- List findings using exactly three severity levels: **Critical**, **Moderate**, **Minor**.
- If there are any findings, add comments to the PR about where you found these errors. If that is not possible, create a new comment on the PR with the list of findings. If you do not find any issues, post a short comment stating that **no findings were identified**. Every text in English.
- I don't want to enter technical data into the JIRA issue tracker after code review, but I want to edit the text so that project managers and testers can understand it.
- For simple fixes, include a minimal example using JIRA `{code}` blocks only when it improves clarity.
- I want you to use the console cli tool to insert the CR result into the GitHub PR as a new comment. The PR comment must contain **only findings** grouped by severity (Critical → Moderate → Minor), each with file/line (or file) and a short, actionable recommendation. Do not include any summary, “what was checked”, or praise.
- Run the tests and let me know if the current changes meet the requirements. If so, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If the requirements are not met or you have found critical errors, just list them for me.
- If is needed use interactive-testing skill for testing

**Communication protocol:**
- Do not include praise/positive feedback; output must contain only findings.
- If you find significant deviations from the plan or requirements, explicitly flag them and ask for confirmation.
- If you identify issues with the original plan or requirements themselves, recommend updates.
- For implementation problems, provide clear guidance on fixes needed with code examples.

**After completing the tasks**
- If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
- Based on the discussion in the assignment, is the proposed solution to the problems safe and effective? Analyze the assignment and all discussions related to this task and write me your conclusion!
