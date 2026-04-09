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
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- All CR output (findings, recommendations, comments) must be written in English.
- Never push direct changes to the main branch.
- If the pull request has merge conflicts with the base branch, stop and report that the code review processing is blocked.
- Apply @rules/jira-operations.mdc

**Steps:**
- Identify the task from the provided issue code or URL.
- Find all open pull requests for that task. If there are multiple open PRs, process each one sequentially — apply the full review-feedback resolution cycle to each PR independently before moving to the next.
- In each pull request, locate code review output and all review comments (including review threads and general comments).
- If there is only a generic `CR` comment, treat it as `code review` feedback.
- Build a checklist from all review findings and map each item to a concrete code or test change.
- Ensure the checklist explicitly contains all reported **DRY violations** and tracks their resolution before triggering the next CR cycle.
- Apply the requested changes and keep scope limited to review feedback. All new or modified production code must follow @skills/class-refactoring/SKILL.md.
- **Simplification analysis:** Evaluate whether the solution can be written more simply without altering the new logic, leveraging rules and conventions already defined in `rules/**/*.mdc`. Flag unnecessary complexity as a finding.
- Re-check current changes with @skills/code-review/SKILL.md and @skills/security-review/SKILL.md.  
- If review feedback requires additional tests, use @skills/create-missing-tests-in-pr/SKILL.md and ensure current changes are fully covered.
- If new database migrations were created during the changes, run them (`php artisan migrate`) before running tests or creating a PR.
- Run only checks/tests needed for the changed files and fix all errors before continuing.
- Run the issue-tracker-specific code review skill before PR creation:
  - GitHub issue flow: run @skills/code-review-github/SKILL.md
  - JIRA issue flow: run @skills/code-review-jira/SKILL.md
- Fix all Critical and Moderate findings from that review and repeat the same review skill until no Critical or Moderate findings remain.
- After the CR loop is clean (no **Critical** or **Moderate** findings), run @skills/test-like-human/SKILL.md when the change can be tested. The test-like-human skill must post its unified test report as a comment to the related issue in the issue tracker.
- Commit all changes and push the branch. If no pull request exists for the current branch, create one according to @rules/git/pr.mdc rules — link it to the original issue and follow the PR description format (title in English, body in the language of the assignment). Do not create a new PR before the CR cycle is clean.
- Update the review result comment in the pull request:
- mark resolved points as checked items when possible, or
- format resolved points as underlined text when checkbox updates are not possible.
- If you cannot update the original comment, add a new PR comment with the same resolved-point status.
- After all points are addressed, trigger the next review interaction by issue tracker:
- GitHub: run @skills/code-review-github/SKILL.md
- JIRA: run @skills/code-review-jira/SKILL.md
- Share a concise completion report with PR link, resolved items, and any remaining blockers.

**After completing the tasks**
- Confirm all review points are resolved or explicitly marked as blocked with reasons.
- Ensure the PR contains clear evidence that each review remark was handled.
- Summarize what changed, what was tested, and what requires follow-up.
