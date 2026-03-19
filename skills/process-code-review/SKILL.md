---
name: process-code-review
description: "Use when processing pull request code review feedback. Finds the latest PR for a task, resolves review comments, updates review status, and triggers the next review cycle."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- Never push direct changes to the main branch.
- If the pull request has merge conflicts with the base branch, stop and report that the code review processing is blocked.
- For comments posted to JIRA, always use JIRA Wiki Markup (not Markdown) and follow the universal structure from JIRA-focused skills.

**Steps:**
- Identify the task from the provided issue code or URL.
- Find the latest pull request for that task using available CLI tools or MCP servers.
- In the pull request, locate code review output and all review comments (including review threads and general comments).
- If there is only a generic `CR` comment, treat it as `code review` feedback.
- Build a checklist from all review findings and map each item to a concrete code or test change.
- Apply the requested changes and keep scope limited to review feedback. All new or modified production code must follow @.cursor/skills/class-refactoring/SKILL.md.
- Re-check current changes with @.cursor/skills/code-review/SKILL.md and @.cursor/skills/security-review/SKILL.md.  
- If review feedback requires additional tests, use @.cursor/skills/create-missing-tests-in-pr/SKILL.md and ensure current changes are fully covered.
- Run only checks/tests needed for the changed files and fix all errors before continuing.
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
