---
name: pr-summary
description: "Use when summarizing current PR changes for the development and product team. Analyzes all commits in the current branch, explains the purpose of changes, and produces a clear markdown report understandable by both technical and non-technical stakeholders."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Write the summary in singular first person (one developer made the changes).
- The output must be formatted in markdown.
- Focus on the "why" and business impact, not on implementation details.
- The summary must be understandable by both developers and product managers.
- Do not include code snippets unless they are essential to explain a breaking change.

**Scripts:** Use the pre-built scripts in `@skills/pr-summary/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/branch-commits.sh [base]` | List all commits since branch diverged from base |
| `scripts/branch-diff.sh [base]` | Full diff of branch against base |
| `scripts/pr-context.sh` | Load PR description and linked issues for the current branch |

**References:**
- `references/summary-guidelines.md` — tone, audience, content rules, and formatting requirements
- `references/change-categorization.md` — standard categories and categorization rules
- `references/context-gathering.md` — branch detection, commit analysis, PR/issue context, handling large PRs

**Examples:** See `examples/` for expected output format:
- `examples/summary-feature-branch.md` — feature branch with new functionality
- `examples/summary-bugfix-branch.md` — bugfix with a minor breaking change
- `examples/summary-refactoring-branch.md` — internal refactoring with no user-facing change

**Steps:**
1. Run `scripts/branch-commits.sh` to load all commits in the current branch since it diverged from the base branch.
2. Run `scripts/branch-diff.sh` to load the full diff for analysis.
3. Run `scripts/pr-context.sh` to load the PR description and linked issue(s) for additional context.
4. For each commit, read the commit message and the diff to understand what changed and why.
5. Group the changes into logical categories per `references/change-categorization.md`.
6. Write a markdown summary following the output contract below, applying rules from `references/summary-guidelines.md`.

**Output contract:** The summary must contain the following sections:

| Field | Required | Description |
|---|---|---|
| Branch name | Yes | Identifies the branch in the heading |
| What changed | Yes | Concise paragraph (3-5 sentences) explaining purpose and business impact |
| Changes by category | Yes | Grouped list of changes with affected files or areas |
| Breaking changes | Yes | List of breaking changes, or "No breaking changes." |
| Testing notes | Yes | What was tested or should be verified before deployment |
| Confidence notes | If applicable | Caveats or assumptions (e.g., incomplete context, unclear commit messages) |

**After completing the tasks**
- Post the summary as a comment to the related PR or issue if available.
