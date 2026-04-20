---
name: pr-summary
description: "Use when summarizing current PR changes for the development and product team. Analyzes all commits in the current branch, explains the purpose of changes, and produces a clear markdown report understandable by both technical and non-technical stakeholders."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/php/core-standards.mdc
- Apply @rules/git/general.mdc
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Write the summary in singular first person (one developer made the changes).
- The output must be formatted in markdown.
- Focus on the "why" and business impact, not on implementation details.
- The summary must be understandable by both developers and product managers.
- Do not include code snippets unless they are essential to explain a breaking change.

**Steps:**
1. Identify the current branch and its base branch (usually `master` or `main`).
2. Load all commits in the current branch since it diverged from the base branch (`git log base..HEAD`).
3. For each commit, read the commit message and the diff to understand what changed and why.
4. If a PR already exists for this branch, load the PR description and linked issue(s) for additional context.
5. Group the changes into logical categories (e.g. new features, bug fixes, refactoring, configuration, tests).
6. Write a markdown summary following the output format below.

**Output format:**

Use the template defined in `templates/pr-summary-report.md`.

**After completing the tasks**
- Post the summary as a comment to the related PR or issue if available.

---

## Principles
- Focus on business impact, not technical detail
- Explain the "why", not just the "what"
- Group changes into meaningful categories
- Avoid listing low-value or trivial changes
- Be concise and easy to scan
- Highlight risks and breaking changes first
