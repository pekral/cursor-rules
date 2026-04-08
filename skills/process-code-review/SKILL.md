---
name: process-code-review
description: "Use when processing pull request code review feedback. Finds the latest PR for a task, resolves review comments, updates review status, and triggers the next review cycle."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Never push direct changes to the main branch.
- If the pull request has merge conflicts with the base branch, stop and report that the code review processing is blocked.
- Apply @rules/jira-operations.mdc

**Scripts:** Use the pre-built scripts in `@skills/process-code-review/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/find-prs-for-task.sh <ISSUE>` | Find all open PRs linked to a task/issue |
| `scripts/pr-review-comments.sh <PR>` | Fetch all review comments and threads for a PR |
| `scripts/check-pr-conflicts.sh <PR>` | Check if a PR has merge conflicts with its base branch |

**References:**
- `references/review-checklist-rules.md` — how to build the review checklist, scope changes, and run simplification analysis
- `references/cr-cycle-policy.md` — pre-commit review gates, issue-tracker-specific reviews, iterative resolution, and human-like testing
- `references/pr-update-rules.md` — committing, pushing, updating review status in the PR, triggering next review cycle, and completion verification

**Examples:** See `examples/` for expected output format:
- `examples/report-all-resolved.md` — all review points resolved, clean cycle
- `examples/report-blocked.md` — blocked by conflicts or unresolved critical findings
- `examples/report-multiple-prs.md` — multiple PRs processed for one task

**Steps:**
1. Identify the task from the provided issue code or URL.
2. Run `scripts/find-prs-for-task.sh <ISSUE>` to find all open pull requests for that task. If there are multiple open PRs, process each one sequentially — apply the full review-feedback resolution cycle to each PR independently before moving to the next.
3. Run `scripts/check-pr-conflicts.sh <PR>` to verify there are no merge conflicts. If conflicts exist, stop and report as blocked.
4. In each pull request, run `scripts/pr-review-comments.sh <PR>` to locate code review output and all review comments (including review threads and general comments).
5. Build a review checklist per `references/review-checklist-rules.md` — map each finding to a concrete code or test change, ensure DRY violations are tracked, and run simplification analysis.
6. Apply the requested changes, keeping scope limited to review feedback. All new or modified production code must follow @skills/class-refactoring/SKILL.md.
7. Execute the pre-commit review gates and iterative CR cycle per `references/cr-cycle-policy.md`:
   a. Re-check with @skills/code-review/SKILL.md and @skills/security-review/SKILL.md.
   b. Add missing tests via @skills/create-missing-tests-in-pr/SKILL.md if needed.
   c. Run database migrations if new ones were created (`php artisan migrate`).
   d. Run only checks/tests needed for the changed files and fix all errors.
   e. Run the issue-tracker-specific review skill (GitHub or JIRA).
   f. Fix all Critical and Moderate findings and repeat until none remain.
   g. Run @skills/test-like-human/SKILL.md when the change can be tested; post unified test report to the issue.
8. Commit, push, and update the PR per `references/pr-update-rules.md`:
   a. Commit all changes and push the branch.
   b. Create a PR if none exists, following @rules/git/pr.mdc rules.
   c. Update the review result comment — mark resolved points as checked or underlined.
   d. Trigger the next review cycle by issue tracker.
9. Share a concise completion report with PR link, resolved items, and any remaining blockers.

**Output contract:** For each processed PR, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Task identifier | Yes | Issue code or URL |
| PR number and title | Yes | Identifies the PR |
| Decision | Yes | `review cycle complete` or `blocked` |
| CR findings | Yes | Count of Critical / Moderate remaining |
| Tests | Yes | All passing / N failing |
| Remaining blockers | If blocked | Why the PR cannot proceed |
| Resolved checklist | Yes | Each review item with resolution status and file location |
| Confidence notes | If applicable | Caveats or assumptions (e.g., untestable change, stale review) |
| Next action | Yes | What should happen next |

**After completing the tasks**
- Confirm all review points are resolved or explicitly marked as blocked with reasons.
- Ensure the PR contains clear evidence that each review remark was handled.
- Summarize what changed, what was tested, and what requires follow-up.
