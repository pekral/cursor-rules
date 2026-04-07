---
name: code-review-github
description: "Use when performing code review for GitHub pull requests. Analyzes code changes, identifies critical and moderate issues, runs tests, and posts review comments. Reviews code quality, security, and adherence to project standards."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/skills/base-constraints.mdc
- Apply @rules/skills/review-only.mdc
- Apply @rules/skills/github-operations.mdc
- Always apply @skills/smartest-project-addition/SKILL.md internally to identify one highest-impact, low-risk addition candidate; include it only if it maps to a real finding and keep the final output in the required findings-only format.
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- All comments or outputs posted to GitHub (issues, pull requests, review comments, and PR descriptions) must be written in English.
- Explicitly detect and report **DRY violations** (duplicated logic, duplicated validation rules, repeated branching/condition blocks, and copy-pasted code paths) in every CR result.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.

**Steps:**
- **Multiple PRs per issue:** If the issue has more than one open pull request, perform a separate code review for each open PR sequentially. Review each PR independently on its own branch, post findings to the corresponding PR, and produce a per-PR summary. After all PRs are reviewed, provide a consolidated overview listing each PR with its result (clean / has findings).
- **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts.
- Switch locally to the branch in PR and perform code review over changes locally on the filesystem.
- Before writing findings, collect prior review comments/reports from the PR timeline and related issue discussion. Build a dedup list by problem signature (file/scope + root cause + risk) and skip findings already reported unless severity/impact changed.
- **Plan Alignment Analysis:** Compare the implementation against the original issue description, planning documents, or step description. Identify deviations from the planned approach, architecture, or requirements. Assess whether deviations are justified improvements or problematic departures. Verify that all planned functionality has been implemented — list any missing or only partially met items.
- **Regression analysis:** For every changed file, check whether the modifications could break existing functionality that is NOT part of the ticket scope. Trace callers and dependents of changed methods/classes. If a change alters shared logic (helpers, services, traits, base classes, interfaces), verify that all consumers still behave correctly. Flag any regression risk as a finding — even if the new code is correct in isolation, breaking unrelated features is **Critical**.
- Always apply @skills/code-review/SKILL.md and @skills/security-review/SKILL.md. If the changes include any database-related modifications (migrations, schema changes, repositories, raw SQL, query builder, or Eloquent/queries in changed code), also apply @skills/mysql-problem-solver/SKILL.md for those parts; otherwise do not use the SQL skill. Find the issue by code or URL on GitHub.
- **Race condition review (when shared state is modified):** If the changes contain any of the following signals — read-modify-write sequences, shared counters/balances/stock/quotas, `firstOrCreate`/`updateOrCreate`, retried or re-dispatched jobs that mutate shared records, cache write-back patterns, or bulk read-then-write operations — apply @skills/race-condition-review/SKILL.md. If none of these signals are present, skip this step.
- **I/O bottleneck review (when changes touch file, storage, or external I/O):** If the changes include any of the following signals — synchronous file reads/writes on large or unbounded files, blocking HTTP calls without timeouts, storage operations executed in the request lifecycle, large file responses not streamed, or export/import operations loading all records into memory — flag each occurrence and recommend the appropriate async/streaming pattern. If none of these signals are present, skip this step.
- Apply @rules/skills/architecture-patterns.mdc
- Find the Git branch and switch to it.
- If possible, find links to the assignment and analyze it so you can do a quality CR.
- List findings using exactly three severity levels: **Critical**, **Moderate**, **Minor**.
- If there are any findings, add comments to the PR about where you found these errors. If that is not possible, create a new comment on the PR with the list of findings. If you do not find any issues, post a short comment stating that **no findings were identified**. Every text in English.
- I want you to use the console cli tool to insert the CR result into the GitHub PR as a new comment. The PR comment must contain **only findings** grouped by severity (Critical → Moderate → Minor), each with file/line (or file) and a short, actionable recommendation. Do not include any summary, “what was checked”, or praise.
- Use readable Markdown with clear section separators and include short code suggestions for simple fixes when helpful.
- Run the tests and let me know if the current changes meet the requirements. If so, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If the requirements are not met or you have found critical errors, just list them for me.
- If needed, use browser-based testing via available browser MCP tools
- If all **Critical** and **Moderate** findings from the current CR cycle are resolved, run @skills/test-like-human/SKILL.md before closing the review flow (when the changes are testable).

**Communication protocol:**
- Do not include praise/positive feedback; output must contain only findings.
- If you find significant deviations from the plan or requirements, explicitly flag them and ask for confirmation.
- If you identify issues with the original plan or requirements themselves, recommend updates.
- For implementation problems, provide clear guidance on fixes needed with code examples.

**After completing the tasks**
- Keep @skills/test-like-human/SKILL.md as a required final step only after **Critical** and **Moderate** findings are resolved and the changes are testable.
- Based on the discussion in the assignment, is the proposed solution to the problems safe and effective? Analyze the assignment and all discussions related to this task and write me your conclusion!
