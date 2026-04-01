---
name: process-code-review
description: "Use when processing pull request code review feedback. Finds the latest PR for a task, resolves review comments, updates review status, and triggers the next review cycle."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- For all GitHub operations, prefer GitHub CLI (`gh`) as the primary tool.
- If `gh` is not available or cannot be used, use an available GitHub MCP server as fallback.
- If neither `gh` nor a GitHub MCP server is available, stop and return a failed result explaining that required GitHub tools are missing.
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- Never push direct changes to the main branch.
- If the pull request has merge conflicts with the base branch, stop and report that the code review processing is blocked.
- For comments posted to JIRA, always use JIRA Wiki Markup (not Markdown) and follow the universal structure from JIRA-focused skills.

**Steps:**
- Identify the task from the provided issue code or URL.
- Find the latest pull request for that task using GitHub CLI (`gh`) first; if `gh` is not available, use a GitHub MCP server; if neither is available, stop and return a failed result about missing GitHub tools.
- In the pull request, locate code review output and all review comments (including review threads and general comments).
- If there is only a generic `CR` comment, treat it as `code review` feedback.
- Build a checklist from all review findings and map each item to a concrete code or test change.
- Ensure the checklist explicitly contains all reported **DRY violations** and tracks their resolution before triggering the next CR cycle.
- Apply the requested changes and keep scope limited to review feedback. All new or modified production code must follow @.cursor/skills/class-refactoring/SKILL.md.
- Re-check current changes with @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md.  
- If review feedback requires additional tests, use @.cursor/skills/create-missing-tests-in-pr/SKILL.md and ensure current changes are fully covered.
- If new database migrations were created during the changes, run them (`php artisan migrate`) before running tests or creating a PR.
- Run only checks/tests needed for the changed files and fix all errors before continuing.
- Run the issue-tracker-specific code review skill before PR creation:
  - GitHub issue flow: run @.cursor/skills/code-review-github/SKILL.md
  - JIRA issue flow: run @.cursor/skills/code-review-jira/SKILL.md
- Fix all Critical and Moderate findings from that review and repeat the same review skill until no Critical or Moderate findings remain.
- After the CR loop is clean (no **Critical** or **Moderate** findings), run @.cursor/skills/test-like-human/SKILL.md when the change can be tested.
- Commit all changes and push the branch. If no pull request exists for the current branch, create one according to @.cursor/rules/git/pr.mdc rules — link it to the original issue and follow the PR description format (title in English, body in the language of the assignment). Do not create a new PR before the CR cycle is clean.
- Update the review result comment in the pull request:
- mark resolved points as checked items when possible, or
- format resolved points as underlined text when checkbox updates are not possible.
- If you cannot update the original comment, add a new PR comment with the same resolved-point status.
- After all points are addressed, trigger the next review interaction by issue tracker:
- GitHub: run @.cursor/skills/code-review-github/SKILL.md
- JIRA: run @.cursor/skills/code-review-jira/SKILL.md
- Share a concise completion report with PR link, resolved items, and any remaining blockers.

**After completing the tasks**
- Confirm all review points are resolved or explicitly marked as blocked with reasons.
- Ensure the PR contains clear evidence that each review remark was handled.
- Summarize what changed, what was tested, and what requires follow-up.
