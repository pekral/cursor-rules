---
name: code-review-jira
description: Use when run code review for JIRA issues and publish results to
  GitHub PR and JIRA
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Code Review (JIRA)

## Purpose
Perform code review for JIRA issues by analyzing related pull requests and publishing results to:
- GitHub (technical findings)
- JIRA (human-readable summary)

---

## Constraints
- Apply @rules/jira/general.mdc
- Apply @rules/git/general.mdc
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Never modify code
- GitHub output must be in English
- JIRA output must be understandable for non-developers
- Output findings only (no praise)

---

## Execution

### 1. Load Context
- Load JIRA issue, comments, and attachments
- Identify all open PRs linked to the issue
- Before reviewing a PR, switch to the PR branch and pull latest changes

#### Issue Context Analysis
Before reviewing code, load and analyze the full JIRA issue:

1. Fetch the complete JIRA issue — description, all comments, and all attachments (screenshots, files, embedded data).
2. Extract from the issue:
   - **Requirements and acceptance criteria** — what the code must do
   - **Expected behavior** — how the feature or fix should work
   - **Edge cases and constraints** — mentioned by the reporter or in comments
   - **Test data** — any sample inputs, payloads, or scenarios provided in the issue
3. Use this context to evaluate whether the implementation fully satisfies the issue — not just whether the code is technically correct.
4. If the issue contains test data or test scenarios, verify they are covered by existing or new tests. Flag missing test coverage as a finding.

### 2. Pre-checks
- If PR has conflicts → skip review for that PR

### 3. Run Reviews

- For each PR:
  - run @skills/code-review/SKILL.md
  - run @skills/security-review/SKILL.md

- Run conditionally:
  - DB changes → @skills/mysql-problem-solver/SKILL.md
  - Shared state → @skills/race-condition-review/SKILL.md

### 4. Publish Results

#### GitHub (technical findings only)
- Post all technical findings as PR comment
- Format:
  - Critical → Moderate → Minor
  - file + line
  - actionable fix
- This is the only place where technical details appear

#### JIRA (non-technical summary only)
- Never post file paths, line numbers, code snippets, or technical severity levels to JIRA
- Post a plain-language summary:
  - overall status (clean / has issues)
  - key risks described in business terms
  - testing recommendations with step-by-step instructions
  - link to the GitHub PR for full technical details

---

## Output Rules

### GitHub (technical report — only here)
- All technical findings go exclusively to GitHub PR comments
- Include: file paths, line numbers, code references, severity levels, concrete fixes
- Findings only — no praise, no explanations of what was checked
- Use severity levels:
  - Critical
  - Moderate
  - Minor
- If reviewed code violates project rules or architecture but is **out of scope** for the current PR, add a **Refactoring Proposals** section with issue drafts:
  - Title, scope, violated rule/principle, and suggested approach per proposal
  - Only propose refactoring justified by defined rules — not stylistic preferences
  - Omit this section if no opportunities are found
- End with:
  **Summary: X Critical, Y Moderate, Z Minor**

### JIRA (non-technical summary — only here)
- Never include file paths, line numbers, code snippets, or technical severity levels
- Write in plain language understandable by non-developers
- Include:
  - overall status (clean / has issues)
  - key risks described in business terms
  - testing recommendations with step-by-step instructions
  - link to the GitHub PR for technical details

---

## Principles

- Focus on risks, not style
- Prefer impact over quantity
- Avoid duplication of findings
- Prioritize regression detection
- Be precise and actionable

---

## After Completion

- If no **Critical** or **Moderate** findings:
  - run @skills/test-like-human/SKILL.md
