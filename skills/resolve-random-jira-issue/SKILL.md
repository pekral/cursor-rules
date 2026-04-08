---
name: resolve-random-jira-issue
description: "Use when resolving random JIRA issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Links PRs to JIRA issues and updates issue status."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Apply @rules/jira-operations.mdc
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch. See `references/git-branch-policy.md`.
- If you are not on the main git branch in the project, switch to it.
- Pull request creation is mandatory for every resolved JIRA issue selected by this skill. Do not finish without a GitHub PR URL linked to the selected JIRA issue.

**Scripts:** Use the pre-built scripts in `@skills/resolve-random-jira-issue/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/list-jira-candidates.sh <PROJECT>` | List unresolved JIRA issues labeled `Resolve_by_AI` |

**References:**
- `references/issue-selection-criteria.md` — filtering rules, eligible issue definition, random selection policy
- `references/completion-criteria.md` — definition of done, PR linkage, JIRA status transition requirements
- `references/git-branch-policy.md` — pre-resolution branch state, pulling latest, clean working tree

**Examples:** See `examples/` for expected output format:
- `examples/report-issue-resolved.md` — successful resolution with PR linked
- `examples/report-no-candidates.md` — no eligible issues found

**Steps:**
1. Ensure the working tree is on the main branch with latest changes per `references/git-branch-policy.md`.
2. Run `scripts/list-jira-candidates.sh <PROJECT>` (or use the preferred JIRA tool from @rules/jira-operations.mdc) to list all unresolved issues labeled `Resolve_by_AI`.
   - Search the default project board and any additional configured locations.
   - Only include issues that are not resolved.
3. Filter candidates per `references/issue-selection-criteria.md` and randomly select one.
4. Delegate resolution to @skills/resolve-jira-issue/SKILL.md for the selected issue.
5. Verify completion per `references/completion-criteria.md`:
   a. A GitHub PR was created.
   b. The PR is linked in the selected JIRA issue.
   c. The JIRA issue status was transitioned appropriately.
6. If any completion criterion is not met, report what is missing and do not mark the task as done.

**Output contract:** For each run, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| JIRA issue key and summary | Yes | Identifies the selected issue |
| Decision | Yes | `resolved` or `no candidates` or `incomplete` |
| PR number and title | If resolved | The GitHub PR created for this issue |
| PR linked in JIRA | If resolved | Whether the PR URL was added to the JIRA issue |
| JIRA status | If resolved | The new status of the JIRA issue |
| Tests | If resolved | Whether all tests pass |
| Blocking reasons | If incomplete | Why the resolution could not be completed |
| Confidence notes | If applicable | Caveats or assumptions (e.g., flaky tests, ambiguous requirements) |
| Next action | Yes | What should happen next |
