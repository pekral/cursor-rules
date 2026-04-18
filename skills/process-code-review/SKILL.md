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
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
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
- Before processing a PR, switch to the PR branch and pull latest changes

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

### Pre-push quality gates

- Discover available fixers and checkers (prefer Phing targets from `build.xml`/`phing.xml`; fall back to Composer scripts in `composer.json`)
- Run available fixers on all changed files and fix any violations
- Run available checkers/analyzers on all changed files and resolve all reported errors

### Finalization

- Run @skills/test-like-human/SKILL.md if changes are testable
- Commit and push changes
- If PR does not exist, create it according to @rules/git/general.mdc
  - Title in English
  - Body in assignment language

---

### PR update

- Find the original code review comment on the PR:
  - Use `gh api` to list PR comments and identify the CR comment (e.g. contains "Summary:" with severity counts)
- **If the original CR comment is found:**
  - Post resolved items and status updates as a new PR comment that references the original CR comment
  - GitHub does not support native replies to issue comments — use quoting (e.g. "> Replying to code review from {date}") to create a visual thread
- **If original comment cannot be found or edited:**
  - Add a new top-level PR comment with resolved-point status
- Mark resolved items (checkbox or inline) in all cases

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
