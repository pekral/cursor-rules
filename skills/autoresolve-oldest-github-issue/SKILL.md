---
name: autoresolve-oldest-github-issue
description: "Use when autonomously resolving the oldest open GitHub issue end-to-end. Picks the oldest open issue (optionally filtered by label, default `Resolve_by_AI`), delegates resolution to `resolve-issue`, then runs `code-review-github`, `process-code-review`, and `merge-github-pr` on the resulting pull request. Stops and reports any blocker (merge conflict, failing CI, unresolved Critical/Moderate findings) instead of force-merging."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/git/general.mdc`
- Apply `@rules/reports/general.mdc`
- Operate on the current Git repository's GitHub remote only — refuse if the remote is not GitHub
- Process exactly **one** issue per invocation; never loop into a second issue
- Never bypass quality gates of the delegated skills (`resolve-issue`, `code-review-github`, `process-code-review`, `merge-github-pr`)
- Never force-merge: stop on merge conflict, failing CI, missing approvals, or unresolved Critical/Moderate CR findings
- Never alter the original issue body, labels, or assignees outside what the delegated skills already do
- Do not expose sensitive/internal details in user-facing messages

## Use when
- The user wants the oldest open GitHub issue auto-resolved end-to-end (resolve → review → process feedback → merge)
- A scheduled or batch workflow needs a single deterministic entry point that chains the four skills

## Inputs
- `LABEL` (optional) — GitHub label used to filter eligible issues. Default: `Resolve_by_AI`. Pass an empty string (`LABEL=""`) to disable label filtering and pick the globally oldest open issue.

## Execution

### 1. Preflight
- Confirm `gh auth status` reports an authenticated session; if not, stop and ask the user to authenticate.
- Resolve the current Git remote origin and verify it points to GitHub. Stop with a clear message otherwise.
- Switch to the `main` branch and pull the latest changes.

### 2. Select the oldest issue
- Resolve the current repository slug: `REPO=$(gh repo view --json nameWithOwner -q .nameWithOwner)`.
- Query the single oldest open issue via `gh search issues`, which — unlike `gh issue list` — supports explicit ascending order and therefore returns the **globally** oldest match regardless of total open-issue count:
  ```
  QUERY="is:open is:issue repo:${REPO}${LABEL:+ label:\"$LABEL\"}"
  gh search issues "$QUERY" --sort created --order asc --limit 1 \
      --json number,url,title,createdAt,labels,assignees
  ```
  Do **not** substitute `gh issue list --sort created --limit <N>`: that command returns the newest `N` issues with no `--order` switch, so any client-side ascending sort picks the oldest of the newest, never the true oldest.
- If the result is empty, stop with the message `No eligible open GitHub issues found (label=<LABEL>)`.
- Record the selected issue's `number` and `url`. This URL is the single argument passed to every downstream skill in the chain.

### 3. Resolve the issue
- Invoke `@skills/resolve-issue/SKILL.md` with the selected issue URL.
- That skill handles branching, implementation, tests, pre-push gates, the local code-review / security-review loop, PR creation, and reports per its own contract.
- When `resolve-issue` finishes, capture the resulting PR URL from its output. If no PR URL is produced, stop and report the failure — do **not** continue the chain.

### 4. Run code review on the PR
- **Delegate to a subagent.** Dispatch `@skills/code-review-github/SKILL.md` via the `Agent` tool (`subagent_type: "general-purpose"`) and pass the **PR URL** (not the issue URL) plus the instruction "run `@skills/code-review-github/SKILL.md` against this PR and return the published PR comment URL, the linked-issue comment URL(s), and the Critical / Moderate / Minor counts". Running the CR through a subagent isolates the CR's context (deterministic loader output, sub-review findings, posted comment markdown) from this orchestrator's window so the chain can drive long PRs without exhausting context. Fall back to invoking the skill in-line only when subagent dispatch is unavailable.
- The CR skill's deterministic loader accepts a PR URL or number and posts findings as a fresh PR comment plus a non-technical mirror on the linked issue.

### 5. Process review feedback
- **Delegate to a subagent.** Dispatch `@skills/process-code-review/SKILL.md` via the `Agent` tool and pass the **PR URL** plus the instruction "drive the review loop on this PR to convergence (Critical + Moderate == 0) and return the iteration count, residual finding counts, and the final status comment URL". Same rationale as step 4 — the convergence loop's per-iteration findings and fix diffs stay in the subagent's context.
- This is the convergence loop: it resolves comments, applies Suggested Fix snippets, re-runs the review in quiet mode, and exits when `criticalCount + moderateCount == 0` (or after its `maxIterations` safety net).
- If the subagent reports residual Critical or Moderate findings, **stop**. Report the residual findings and the PR URL; do not attempt the merge.

### 6. Merge the PR
- Invoke `@skills/merge-github-pr/SKILL.md` with the **PR URL**.
- The merge skill performs its own pre-checks (`mergeable`, `mergeStateStatus`, `statusCheckRollup[]`, `reviewDecision`) and will skip the merge with a reason on any failure. Surface that reason verbatim in the final report.

### 7. Stop on blockers
At any step, stop the chain and produce the final report when:
- `resolve-issue` does not produce a PR
- `code-review-github` reports the PR has merge conflicts (review cancelled per its own contract)
- `process-code-review` cannot drive Critical + Moderate findings to zero within its iteration cap
- `merge-github-pr` reports `mergeable != MERGEABLE`, `mergeStateStatus` in `DIRTY` / `BEHIND`, any non-passing entry in `statusCheckRollup[]`, or `reviewDecision != APPROVED`

Never retry or "fix" the blocker outside the contract of the delegated skill that surfaced it.

## Output

A short report containing:
- Selected issue: `#<number>` + URL + `createdAt`
- PR created by `resolve-issue`: URL (or the failure reason if none)
- `code-review-github` outcome: counts of Critical / Moderate / Minor (or `skipped — <reason>`)
- `process-code-review` outcome: iterations run, residual Critical / Moderate count (or `skipped — <reason>`)
- `merge-github-pr` outcome: `merged` / `skipped — <reason>`
- For every skipped step, the verbatim reason returned by the delegated skill

## Done when
- Exactly one issue was selected and either fully merged or the chain stopped at a documented blocker
- Every delegated skill that ran finished according to its own `Done when` contract
- The final report lists the four step outcomes and the PR URL (when one was created)
