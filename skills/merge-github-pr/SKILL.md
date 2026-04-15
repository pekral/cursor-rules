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

### 2. Pre-checks (must all pass)

For each PR:

- No merge conflicts
- CI is passing
- Required approvals are present
- Branch is up to date with base branch

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
