---
name: code-review-github
description: "Use when performing code review for GitHub pull requests. Analyzes code changes, identifies critical and moderate issues, runs tests, and posts review comments. Reviews code quality, security, and adherence to project standards."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/review-only.mdc
- Apply @rules/github-operations.mdc
- Always apply @skills/smartest-project-addition/SKILL.md internally to identify one highest-impact, low-risk addition candidate; include it only if it maps to a real finding and keep the final output in the required findings-only format.
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- All comments or outputs posted to GitHub (issues, pull requests, review comments, and PR descriptions) must be written in English.
- Explicitly detect and report **DRY violations** (duplicated logic, duplicated validation rules, repeated branching/condition blocks, and copy-pasted code paths) in every CR result.
- Analyze all comments in the issue and create a list of tasks from the assignment and comments so that you can resolve all issues, if they have not already been resolved.

**Scripts:** Use the pre-built scripts in `@skills/code-review-github/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/pr-detail.sh <PR>` | Full PR context: body, reviews, comments, CI checks |
| `scripts/pr-diff.sh <PR>` | Full diff for the PR |
| `scripts/pr-comments.sh <PR>` | All PR comments and inline review threads for dedup |
| `scripts/post-review-comment.sh <PR> <FILE>` | Post a review comment to the PR |
| `scripts/issue-detail.sh <ISSUE>` | Issue body and comments for plan alignment |

**References:**
- `references/review-severity-levels.md` — definition of Critical, Moderate, Minor severity levels
- `references/plan-alignment-analysis.md` — how to compare implementation against the plan/issue
- `references/regression-analysis.md` — procedure for tracing callers and detecting regressions
- `references/dedup-policy.md` — deduplication of findings from prior review cycles
- `references/communication-protocol.md` — output format rules, language, escalation
- `references/specialized-review-triggers.md` — when to invoke race-condition, I/O, database, and other sub-skills

**Examples:** See `examples/` for expected output format:
- `examples/report-findings.md` — PR with findings grouped by severity
- `examples/report-clean.md` — PR with no findings
- `examples/report-conflict-skip.md` — PR skipped due to merge conflicts
- `examples/report-multi-pr.md` — multiple PRs reviewed for one issue

**Steps:**
1. **Multiple PRs per issue:** If the issue has more than one open pull request, perform a separate code review for each open PR sequentially. Review each PR independently on its own branch, post findings to the corresponding PR, and produce a per-PR summary. After all PRs are reviewed, provide a consolidated overview listing each PR with its result (clean / has findings). See `examples/report-multi-pr.md`.
2. **Cancel CR if PR has conflicts!** If the PR has merge conflicts with the base branch, do not perform the code review; cancel and report that the CR was skipped due to conflicts. See `examples/report-conflict-skip.md`.
3. Switch locally to the branch in PR and perform code review over changes locally on the filesystem. Use `scripts/pr-detail.sh <PR>` and `scripts/pr-diff.sh <PR>` to gather context.
4. Before writing findings, collect prior review comments/reports using `scripts/pr-comments.sh <PR>` and apply `references/dedup-policy.md` to skip already-reported findings.
5. **Plan Alignment Analysis:** Compare the implementation against the original issue description, planning documents, or step description per `references/plan-alignment-analysis.md`. Use `scripts/issue-detail.sh <ISSUE>` to load issue context.
6. **Simplification analysis:** Evaluate whether the solution can be written more simply without altering the new logic, leveraging rules and conventions already defined in `rules/**/*.mdc`. Flag unnecessary complexity as a finding.
7. **Regression analysis:** For every changed file, apply `references/regression-analysis.md`. Trace callers and dependents of changed methods/classes. If a change alters shared logic, verify that all consumers still behave correctly. Flag any regression risk as a finding.
8. Apply specialized sub-skills per `references/specialized-review-triggers.md`: always apply `@skills/code-review/SKILL.md` and `@skills/security-review/SKILL.md`. Conditionally apply database, race-condition, and I/O reviews based on the triggers defined in that reference.
9. Apply @rules/architecture-patterns.mdc
10. List findings using exactly three severity levels per `references/review-severity-levels.md`: **Critical**, **Moderate**, **Minor**.
11. Post findings to the PR per `references/communication-protocol.md`. Use `scripts/post-review-comment.sh <PR> <FILE>` to post the comment. If no issues are found, post a short comment stating that **no findings were identified**. See `examples/report-findings.md` and `examples/report-clean.md`.
12. Run the tests and let me know if the current changes meet the requirements. If so, add a new comment to the issue with brief testing recommendations and include direct in-app links (full URLs) for each recommendation so testers can click through immediately. If the requirements are not met or you have found critical errors, just list them for me.
13. If needed, use browser-based testing via available browser MCP tools.
14. If all **Critical** and **Moderate** findings from the current CR cycle are resolved, run @skills/test-like-human/SKILL.md before closing the review flow (when the changes are testable). The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.

**Communication protocol:** See `references/communication-protocol.md` for full rules. Key points:
- Do not include praise/positive feedback; output must contain only findings.
- If you find significant deviations from the plan or requirements, explicitly flag them and ask for confirmation.
- If you identify issues with the original plan or requirements themselves, recommend updates.
- For implementation problems, provide clear guidance on fixes needed with code examples.

**After completing the tasks:**
- Keep @skills/test-like-human/SKILL.md as a required final step only after **Critical** and **Moderate** findings are resolved and the changes are testable. The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.
- Based on the discussion in the assignment, is the proposed solution to the problems safe and effective? Analyze the assignment and all discussions related to this task and write me your conclusion!

**Output contract:** For each reviewed PR, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| PR number and title | Yes | Identifies the PR |
| Review result | Yes | `clean` or `has findings` |
| Conflict status | Yes | None / present (if present, review is skipped) |
| Findings by severity | If has findings | Grouped as Critical, Moderate, Minor per `references/review-severity-levels.md` |
| File/line references | Per finding | Location of each finding in the codebase |
| Actionable recommendation | Per finding | Short, specific fix guidance |
| Plan alignment | Yes | Deviations from the issue/plan, or "fully aligned" |
| Testing recommendations | If tests pass | Brief recommendations with direct in-app URLs |
| Confidence notes | If applicable | Caveats or assumptions (e.g., untestable paths, ambiguous requirements) |
| Conclusion | Yes | Assessment of whether the proposed solution is safe and effective |
