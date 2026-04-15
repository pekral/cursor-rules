---
name: code-review-github
description: Use when perform code review for GitHub pull requests and post
  findings as PR comments
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Code Review (GitHub)

## Purpose
Run a full code review for GitHub pull requests and publish findings directly to the PR.

---

## Constraints
- Apply @rules/git/general.mdc
- All output posted to GitHub must be in English
- Never modify code
- Output findings only (no praise)

---

## Execution

### 1. Load Context
- Load PR, linked issue, and comments using CLI or MCP tools
- If multiple PRs exist for one issue, review each independently

### 2. Pre-checks
- If PR has merge conflicts → cancel review

### 3. Run Reviews

- Always run:
    - @skills/code-review/SKILL.md
    - @skills/security-review/SKILL.md

- Run conditionally:
    - Database changes → @skills/mysql-problem-solver/SKILL.md
    - Shared state → @skills/race-condition-review/SKILL.md

### 4. Post Results

- Post findings as a PR comment using CLI tools
- Format:
    - Critical → Moderate → Minor
    - include file + line
    - include actionable fix

- If no findings:
    - post: "No findings identified"

---

## Output Rules

- Findings only
- No praise
- No “what was checked”
- Use exactly three severity levels:
    - Critical
    - Moderate
    - Minor

- End with summary:
**Summary: X Critical, Y Moderate, Z Minor**
