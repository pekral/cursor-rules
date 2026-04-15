---
name: process-code-review
description: "Use when processing pull request code review feedback. Finds the latest PR for a task, resolves review comments, updates review status, and triggers the next review cycle."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/php/core-standards.mdc
- Apply @rules/git/general.mdc
- Apply @rules/jira/general.mdc
- Never combine multiple languages in your answer
- All CR output must be written in English
- Never push direct changes to the main branch
- If the pull request has merge conflicts with the base branch, stop and report it
- Do not introduce new logic unrelated to review feedback

---

## Steps

- Identify the task from the provided issue code or URL
- Find all open pull requests for the task
  - If multiple PRs exist, process each independently

### For each PR:

- Load all review comments (including threads and general comments)
- Build a checklist from all review findings
- Map each finding to a concrete code or test change

---

### Pre-fix phase

- Scan affected files for pre-existing bugs
- Fix them in a **separate commit** before applying review fixes

---

### Apply fixes

- Apply only requested review changes
- Keep scope strictly limited to review feedback
- Ensure DRY violations are included and resolved
- All production code changes must follow:
  - @skills/class-refactoring/SKILL.md

---

### Testing

- If tests are required or missing:
  - Run @skills/create-missing-tests-in-pr/SKILL.md
- Ensure current changes have 100% coverage
- Run only relevant tests for changed files
- If migrations were added, run `php artisan migrate`

---

### Review loop

- Run the appropriate review skill:
  - GitHub: @skills/code-review-github/SKILL.md
  - JIRA: @skills/code-review-jira/SKILL.md

- Fix findings and repeat until:
  - No **Critical** or **Moderate** issues remain

---

### Finalization

- Run @skills/test-like-human/SKILL.md if changes are testable
- Commit and push changes
- If PR does not exist, create it according to @rules/git/general.mdc
  - Title in English
  - Body in assignment language

---

### PR update

- Update review comments:
  - Mark resolved items (checkbox or inline)
- If original comment cannot be edited, add a new one

---

### Completion

- Trigger final review:
  - GitHub: @skills/code-review-github/SKILL.md
  - JIRA: @skills/code-review-jira/SKILL.md

- Share a concise completion report:
  - PR link
  - resolved items
  - remaining blockers (if any)

---

## Principles

- Resolve review feedback, do not expand scope
- Prefer minimal changes over unnecessary refactoring
- Do not introduce new bugs while fixing existing ones
- Keep changes traceable to review comments
- Ensure every review comment is explicitly addressed
- Avoid unnecessary commits or noise
