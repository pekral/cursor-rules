---
name: merge-github-pr
description: Use when safely merge GitHub pull requests that are ready
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Merge GitHub PR

## Purpose
Merge pull requests that meet all required conditions.

---

## Constraints
- Apply @rules/git/general.mdc
- Never merge PRs with conflicts
- Never merge PRs with failing CI (unless explicitly instructed)
- Never bypass required approvals or protections

---

## Execution

### 1. Load PRs
- Identify candidate PRs ready for merge
- For each candidate, load PR context by running `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — the single deterministic entry point. Never call `gh pr view`, `gh pr checks`, or `gh api /repos/.../pulls/...` directly. Read `mergeable`, `mergeStateStatus`, `reviewDecision`, and `statusCheckRollup[]` off the resulting JSON document.
- If the script is unavailable (missing tool, exit code 2/3) fall back to the GitHub MCP server.

### 2. Pre-checks (must all pass)

For each PR, derive the verdict from the JSON document loaded in step 1:

- No merge conflicts — `mergeable == "MERGEABLE"` and `mergeStateStatus` is not `DIRTY` or `BEHIND`
- CI is passing — every entry in `statusCheckRollup[]` has a passing `state` (`SUCCESS` / `NEUTRAL` / `SKIPPED`)
- Required approvals are present — `reviewDecision == "APPROVED"`
- Branch is up to date with base branch — `mergeStateStatus != "BEHIND"`

If any check fails:
- do not merge
- report reason

### 3. Merge

- Merge PR using CLI
- Use project default merge strategy

### 4. Post-merge

- Delete branch (if configured)
- Confirm merge success

---

## Output

- List merged PRs
- List skipped PRs with reasons

---

## Principles

- Safety over speed
- Never bypass CI or review gates
- Merge only fully ready PRs
- Be explicit about skipped PRs
