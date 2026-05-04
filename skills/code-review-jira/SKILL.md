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
- Never modify code
- GitHub output must be in English
- JIRA output must be understandable for non-developers
- Output findings only (no praise)

---

## Execution

### 1. Load Context
- Load JIRA issue, comments, and attachments using `acli`. If `acli` is unavailable, fall back to JIRA MCP server.
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
  - Third-party API or service changes → ensure the **Third-Party API & Service Analysis** step from `@skills/code-review/SKILL.md` is executed for the diff

### 4. Publish Results

#### GitHub (technical findings only)
- If a previous CR exists for the same PR, analyze all previous findings and classify each as: **Resolved**, **Deferred**, or **Still open**
- Include a **Previous CR Status** section at the top of the GitHub comment (before new findings)
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
- Use severity levels: Critical, Moderate, Minor
- Each **Critical** and **Moderate** finding must include:
    - **Faulty Example** — minimal code snippet or input payload reproducing the issue (redact secrets/PII)
    - **Expected Behavior** — single assertable statement (return value, exception, persisted state, emitted event)
    - **Test Hint** — one sentence pointing at the test layer (unit, integration, feature) and entry point
- These three fields exist so `@skills/process-code-review/SKILL.md` can convert each finding into a reproducer test directly from the PR comment.
- Minor findings may omit these fields when no behavior change is implied.
- Use the template defined in `templates/github-output.md`

### JIRA (non-technical summary — only here)
- Never include file paths, line numbers, code snippets, or technical severity levels
- Write in plain language understandable by non-developers
- Use the template defined in `templates/jira-output.md`

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
