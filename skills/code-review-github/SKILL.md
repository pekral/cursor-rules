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

#### Thread detection
- Before posting, search for an existing code review comment on the PR:
  - Use `gh api` to list PR comments and find one matching the CR format (e.g. contains "Summary:" with severity counts)
  - Store its `comment_id` if found

#### Posting strategy
- **If an existing CR comment is found (follow-up review):**
    - Post a **summary-only** top-level PR comment (e.g. status update, summary line)
    - Post **detailed findings** as a reply to the original CR comment using:
      `gh api repos/{owner}/{repo}/issues/comments/{comment_id}/replies -f body="..."` or by quoting the original comment
    - If the API reply endpoint is unavailable, post details as a new comment referencing the original (e.g. "> Replying to code review from {date}")

- **If no existing CR comment is found (first review):**
    - Post findings as a single PR comment using CLI tools

#### Format
- Critical → Moderate → Minor
- Include file + line
- Include actionable fix

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
