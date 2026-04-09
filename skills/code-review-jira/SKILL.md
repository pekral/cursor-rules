---
name: code-review-jira
description: "Use when performing code review for JIRA issues. Analyzes pull requests, identifies critical and moderate issues, runs tests, and posts review comments to GitHub PRs. Reviews code quality, security, and adherence to project standards."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/review-only.mdc
- Apply @rules/github-operations.mdc
- Apply @rules/jira-operations.mdc
- Always apply @skills/smartest-project-addition/SKILL.md internally to identify one highest-impact, low-risk addition candidate; include it only if it maps to a real finding and keep the final output in the required findings-only format.
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- All comments or outputs posted to GitHub (issues, pull requests, review comments, and PR descriptions) must be written in English.
- Explicitly detect and report **DRY violations** (duplicated logic, duplicated validation rules, repeated branching/condition blocks, and copy-pasted code paths) in every CR result.

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
- First, find all open pull requests that are automatically linked to JIRA via the branch name. If you can’t find a PR by the branch name, review all the comments in the issue and locate the relevant PRs. Always check only the open pull requests and ignore the rest!
- **Multiple PRs per issue:** If the issue has more than one open pull request, perform a separate code review for each open PR sequentially. Review each PR independently on its own branch, post findings to the corresponding PR, and produce a per-PR summary. After all PRs are reviewed, provide a consolidated overview listing each PR with its result (clean / has findings).
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review for that PR; cancel and report that the CR was skipped due to conflicts. Continue with the next PR.
- Switch locally to the branch in PR and perform code review over changes locally on the filesystem.
- Before writing findings, collect prior review comments/reports from the PR timeline and JIRA discussion. Build a dedup list by problem signature (file/scope + root cause + risk) and skip findings already reported unless severity/impact changed.
- **Plan Alignment Analysis:** Compare the implementation against the original issue description, planning documents, or step description. Identify deviations from the planned approach, architecture, or requirements. Assess whether deviations are justified improvements or problematic departures. Verify that all planned functionality has been implemented — list any missing or only partially met items.
- **Simplification analysis:** Evaluate whether the solution can be written more simply without altering the new logic, leveraging rules and conventions already defined in `rules/**/*.mdc`. Flag unnecessary complexity as a finding.
- **Regression analysis:** For every changed file, check whether the modifications could break existing functionality that is NOT part of the ticket scope. Trace callers and dependents of changed methods/classes. If a change alters shared logic (helpers, services, traits, base classes, interfaces), verify that all consumers still behave correctly. Flag any regression risk as a finding — even if the new code is correct in isolation, breaking unrelated features is **Critical**.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
- Always apply @skills/code-review/SKILL.md and @skills/security-review/SKILL.md. If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed code), also apply @skills/mysql-problem-solver/SKILL.md for those parts; otherwise do not use the SQL skill.
  Retrieve the JIRA issue (by code or URL) using the preferred JIRA tool (see @rules/jira-operations.mdc).
- **Race condition review (when shared state is modified):** If the changes contain any of the following signals — read-modify-write sequences, shared counters/balances/stock/quotas, `firstOrCreate`/`updateOrCreate`, retried or re-dispatched jobs that mutate shared records, cache write-back patterns, or bulk read-then-write operations — apply @skills/race-condition-review/SKILL.md. If none of these signals are present, skip this step.
- **I/O bottleneck review (when changes touch file, storage, or external I/O):** If the changes include any of the following signals — synchronous file reads/writes on large or unbounded files, blocking HTTP calls without timeouts, storage operations executed in the request lifecycle, large file responses not streamed, or export/import operations loading all records into memory — flag each occurrence and recommend the appropriate async/streaming pattern. If none of these signals are present, skip this step.
- Apply @rules/architecture-patterns.mdc
- Find the Git branch and switch to it (if needed pull the latest changes).
- I want you to fix the bug from JIRA (you have either the ID or a link to JIRA). Use the preferred JIRA tool (see @rules/jira-operations.mdc) to retrieve all the information you need about the bug (including comments and attachments). If you have other resources available that you could use to understand the problem, load them and analyze them.
- If possible, find links to the assignment and analyze it so that you understand it and can do a quality CR. Find the attachments for the assignment and analyze them. Again, use the available MCP servers or CLI tools for the specific issue tracker. If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- List findings using exactly three severity levels: **Critical**, **Moderate**, **Minor**.
- Use numbered lists for findings — do not use bullet points.
- **Code coverage for changed files must be 100%.** If coverage is below 100% for any changed file, report it as a **Critical** finding.
- If there are any findings, add comments to the PR about where you found these errors. If that is not possible, create a new comment on the PR with the list of findings. If you do not find any issues, post a short comment stating that **no findings were identified**. Every text in English.
- I don't want to enter technical data into the JIRA issue tracker after code review, but I want to edit the text so that project managers and testers can understand it.
- For simple fixes, include a minimal example using JIRA `{code}` blocks only when it improves clarity.
- I want you to use the console cli tool to insert the CR result into the GitHub PR as a new comment. The PR comment must contain **only findings** grouped by severity (Critical → Moderate → Minor), each with file/line (or file) and a short, actionable recommendation. Do not include any summary, “what was checked”, or praise.
- Run the tests and let me know if the current changes meet the requirements. If so, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If the requirements are not met or you have found critical errors, just list them for me.
- If needed, use browser-based testing via available browser MCP tools
- If all **Critical** and **Moderate** findings from the current CR cycle are resolved, run @skills/test-like-human/SKILL.md before closing the review flow (when the changes are testable). The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.

**Communication protocol:**
- Do not include praise/positive feedback; output must contain only findings.
- If you find significant deviations from the plan or requirements, explicitly flag them and ask for confirmation.
- If you identify issues with the original plan or requirements themselves, recommend updates.
- For implementation problems, provide clear guidance on fixes needed with code examples.

**After completing the tasks**
- Keep @skills/test-like-human/SKILL.md as a required final step only after **Critical** and **Moderate** findings are resolved and the changes are testable. The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.
- Based on the discussion in the assignment, is the proposed solution to the problems safe and effective? Analyze the assignment and all discussions related to this task and write me your conclusion!
