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
- Explicitly detect and report **DRY violations** per `references/review-criteria.md`.

**Scripts:** Use the pre-built scripts in `@skills/code-review-jira/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/find-linked-prs.sh <JIRA_KEY>` | Find open PRs linked to a JIRA issue by branch name |
| `scripts/pr-detail.sh <PR>` | Full PR context: body, reviews, comments, CI checks |
| `scripts/checkout-pr-branch.sh <PR>` | Checkout PR branch locally and pull latest |
| `scripts/post-pr-comment.sh <PR> <BODY>` | Post a comment to a GitHub PR |

**References:**
- `references/review-criteria.md` — severity levels, DRY violations, findings-only output, communication protocol
- `references/plan-alignment-analysis.md` — plan alignment, simplification analysis, regression analysis
- `references/jira-comment-formatting.md` — JIRA comment shape, formatting rules, testing recommendations
- `references/review-workflow.md` — multi-PR handling, conflict policy, dedup, conditional skill application, post-review steps
- `references/io-and-race-condition-signals.md` — race condition and I/O bottleneck signal detection

**Examples:** See `examples/` for expected output format:
- `examples/pr-comment-findings.md` — PR comment with findings grouped by severity
- `examples/pr-comment-clean.md` — PR comment when no findings
- `examples/jira-comment.md` — JIRA comment with findings and testing recommendations
- `examples/consolidated-multi-pr-report.md` — consolidated report for multi-PR issues

**Steps:**
1. Retrieve the JIRA issue (by code or URL) using the preferred JIRA tool (see @rules/jira-operations.mdc). If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
2. Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.
3. Find the attachments for the assignment and analyze them. Use the available MCP servers or CLI tools for the specific issue tracker. If possible, find links to the assignment and analyze it so that you understand it and can do a quality CR.
4. Run `scripts/find-linked-prs.sh <JIRA_KEY>` to find all open PRs linked to the issue. If no PRs found, review issue comments to locate relevant PRs per `references/review-workflow.md`.
5. For each open PR (handle multiple PRs per `references/review-workflow.md`):
   a. **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, skip this PR per `references/review-workflow.md` conflict policy.
   b. Run `scripts/checkout-pr-branch.sh <PR>` to switch to the branch locally.
   c. Run `scripts/pr-detail.sh <PR>` to load full PR context.
   d. Before writing findings, collect prior review comments/reports and build a dedup list per `references/review-workflow.md`.
   e. Perform **plan alignment analysis**, **simplification analysis**, and **regression analysis** per `references/plan-alignment-analysis.md`.
   f. Always apply @skills/code-review/SKILL.md and @skills/security-review/SKILL.md. Conditionally apply database and race-condition skills per `references/review-workflow.md` and `references/io-and-race-condition-signals.md`.
   g. Apply @rules/architecture-patterns.mdc
   h. List findings using severity levels defined in `references/review-criteria.md` (Critical > Moderate > Minor).
   i. Post findings to the PR using `scripts/post-pr-comment.sh <PR> <BODY>` following the findings-only format in `references/review-criteria.md`. If no findings, post a short comment stating **no findings were identified**.
6. Post a JIRA comment following `references/jira-comment-formatting.md`. Keep the text understandable for project managers and testers.
7. Run the tests and verify requirements are met. If requirements are met, add testing recommendations to the JIRA issue with direct in-app links per `references/jira-comment-formatting.md`. If requirements are not met or critical errors exist, list them.
8. If needed, use browser-based testing via available browser MCP tools.
9. If all **Critical** and **Moderate** findings are resolved and changes are testable, run @skills/test-like-human/SKILL.md. The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.
10. Based on the discussion in the assignment, analyze whether the proposed solution is safe and effective. Analyze the assignment and all discussions related to this task and write your conclusion.

**Output contract:** For each reviewed PR, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| PR number and title | Yes | Identifies the PR |
| JIRA issue key | Yes | Links back to the originating issue |
| Review result | Yes | `clean` or `has findings` or `skipped (conflicts)` |
| Findings count by severity | If has findings | Count per level: Critical / Moderate / Minor |
| Findings list | If has findings | Grouped by severity, each with file/line and recommendation |
| Plan alignment | Yes | Deviations from plan or requirements, if any |
| Testing recommendations | If clean or after fixes | Direct in-app links for testers |
| Confidence notes | If applicable | Caveats or assumptions (e.g., untestable path, stale context) |
| Next action | Yes | What should happen next |
