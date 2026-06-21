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
- **Never merge a PR without a converged code review.** A code review must have been run on the PR's final diff and report **no errors** — 0 Critical + 0 Moderate findings (Minor does not block). This is the hard merge gate from `@rules/git/general.mdc` *Merging*; it is mandatory on every merge and is verified in step 2 below.
- Never merge PRs with conflicts
- Never merge PRs with failing CI (unless explicitly instructed)
- Never bypass required approvals or protections
- The only tolerated CI failure is a **GitHub Actions billing / account-limit error** when the merge is **explicitly requested** (see *GitHub Actions billing exception* below). Any other failure — real test failure, lint, static analysis — still blocks.

---

## Execution

### 1. Load PRs
- Identify candidate PRs ready for merge
- For each candidate, load PR context by running `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — the single deterministic entry point. Never call `gh pr view`, `gh pr checks`, or `gh api /repos/.../pulls/...` directly. Read `mergeable`, `mergeStateStatus`, `reviewDecision`, and `statusCheckRollup[]` off the resulting JSON document.
- If the script is unavailable (missing tool, exit code 2/3) fall back to the GitHub MCP server.

### 2. Pre-checks (must all pass)

For each PR, derive the verdict from the JSON document loaded in step 1:

- **Converged code review on the final diff (hard gate, no exception)** — a code review must have run on the exact commits being merged and report **no errors**: 0 Critical + 0 Moderate findings (Minor does not block). Verify it from the PR's review comments in the loaded JSON: locate the latest code-review status comment (the technical CR comment / convergence status posted by `@skills/code-review-github/SKILL.md` / `@skills/process-code-review/SKILL.md`), confirm it reports `criticalCount + moderateCount == 0`, and confirm it was posted **after** the PR's last commit (a review older than the head commit is stale and does not count). If no code-review comment exists, the latest one still carries Critical / Moderate findings, or it predates the head commit, **do not merge** — report that the code-review gate is unmet and that the review must be run (or re-run) to convergence via `@skills/code-review-github/SKILL.md` + `@skills/process-code-review/SKILL.md` first. This gate is **never** waived — not by an explicit merge request, not by the billing exception below, and not by a GitHub `reviewDecision == "APPROVED"` on its own.
- No merge conflicts — `mergeable == "MERGEABLE"` and `mergeStateStatus` is not `DIRTY` or `BEHIND`
- CI is passing — every entry in `statusCheckRollup[]` has a passing `state` (`SUCCESS` / `NEUTRAL` / `SKIPPED`), **with the single billing exception below** when the merge was explicitly requested
- Required approvals are present — `reviewDecision == "APPROVED"`
- Branch is up to date with base branch — `mergeStateStatus != "BEHIND"`

If any check fails:
- do not merge
- report reason

#### GitHub Actions billing exception (explicit merge only)

A single, narrow exception relaxes the CI-passing check — **only** when the caller explicitly requested the merge (an automatic / opportunistic merge never qualifies):

- **When it applies:** the *only* blocking entries in `statusCheckRollup[]` are GitHub Actions runs that did **not** execute because of a billing / account-limit problem — typically a `state` of `ERROR` (or a workflow that never started) whose detail message is an unambiguous billing notice such as *"The job was not started because recent account payments have failed or your spending limit needs to be increased"*, *"billing"*, or *"spending limit"*. In that case the gate **ignores those specific entries** and allows the merge.
- **Detection must stay conservative.** Treat an entry as a billing failure only when its message clearly names a billing / payment / spending-limit cause. A bare `ERROR` / `FAILURE` with no billing wording is a **real** failure — never assume billing. When in doubt, do not merge: report the ambiguous entry and stop.
- **The exception is billing-only.** It never relaxes any other gate: a missing or non-converged code review (the hard CR gate above), a real CI failure (tests, lint, static analysis) on any non-billing entry, `mergeStateStatus == "DIRTY"` / `"BEHIND"`, an unmergeable state, or `reviewDecision != "APPROVED"` still blocks the merge regardless of the explicit request.
- **Report what was waived.** When the merge proceeds under this exception, list each ignored billing entry (check name + the billing message) in the output so the waiver is auditable.

When the merge was **not** explicitly requested, this exception does not apply — a billing failure blocks like any other failing check.

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
- Never bypass CI or review gates — a converged code review (0 Critical + 0 Moderate) on the final diff is a mandatory precondition for every merge
- Merge only fully ready PRs
- Be explicit about skipped PRs
