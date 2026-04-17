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
- Before reviewing a PR, switch to the PR branch and pull latest changes

#### Issue Context Analysis
Before reviewing code, load and analyze the full linked issue:

1. Fetch the complete GitHub issue — description, all comments, and any referenced attachments or links.
2. Extract from the issue:
   - **Requirements and acceptance criteria** — what the code must do
   - **Expected behavior** — how the feature or fix should work
   - **Edge cases and constraints** — mentioned by the reporter or in comments
   - **Test data** — any sample inputs, payloads, or scenarios provided in the issue
3. Use this context to evaluate whether the implementation fully satisfies the issue — not just whether the code is technically correct.
4. If the issue contains test data or test scenarios, verify they are covered by existing or new tests. Flag missing test coverage as a finding.

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
    - Post **detailed findings** as a new PR comment that references the original CR comment (quote its first line or link to it)
    - GitHub does not support native replies to issue comments — use quoting (e.g. "> Replying to code review from {date}") to create a visual thread

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

- If reviewed code violates project rules or architecture but is **out of scope** for the current PR, add a **Refactoring Proposals** section with issue drafts:
    - Title, scope, violated rule/principle, and suggested approach per proposal
    - Only propose refactoring justified by defined rules — not stylistic preferences
    - Omit this section if no opportunities are found

- End with summary:
**Summary: X Critical, Y Moderate, Z Minor**
