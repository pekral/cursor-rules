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
- Load JIRA issue, comments, and attachments
- Identify all open PRs linked to the issue
- Before reviewing a PR, switch to the PR branch and pull latest changes

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

#### GitHub
- Post findings as PR comment
- Format:
  - Critical → Moderate → Minor
  - file + line
  - actionable fix

#### JIRA
- Post simplified summary:
  - short status (clean / has issues)
  - key risks
  - testing recommendations (with links)

---

## Output Rules

### GitHub
- Findings only
- No praise
- No explanations of what was checked
- Use severity levels:
  - Critical
  - Moderate
  - Minor

- End with:
  **Summary: X Critical, Y Moderate, Z Minor**

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
