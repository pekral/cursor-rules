---
name: create-tracker-issue-from-cr-comments
description: "Create issue tracker issues from code review comments. When a code review produces findings that should be tracked as separate issues (not fixed immediately), this skill extracts those comments and creates properly formatted issues in the project's issue tracker."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Never modify code or resolve review findings — this skill only creates tracking issues.
- Never create duplicate issues — check existing open issues before creating new ones.
- Preserve the original review comment content exactly; only improve formatting for readability.
- All issue content must be written in English.
- Use installed CLI tools for the issue tracker (e.g., `gh`, `jira`, `linear`). Never use a web browser.
- Each code review finding produces at most one issue. Do not split a single finding into multiple issues, and do not merge distinct findings into one issue.

**Scripts:** Use the pre-built scripts in `@skills/create-tracker-issue-from-cr-comments/scripts/` to gather data and create issues. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/extract-cr-comments.sh <PR>` | Extract code review findings from a PR suitable for issue creation |
| `scripts/check-existing-issues.sh <query>` | Search for existing open issues to prevent duplicates |
| `scripts/create-issue.sh <title> <body> <labels>` | Create a new issue in the tracker and assign to current user |

**References:**
- `references/finding-selection-criteria.md` — which code review findings qualify for issue creation and which do not
- `references/issue-formatting-rules.md` — how to format the issue title, body, and labels from a CR finding
- `references/deduplication-strategy.md` — how to detect and handle duplicate or overlapping issues

**Examples:** See `examples/` for expected output format:
- `examples/issues-created-from-cr.md` — typical run creating multiple issues from a CR
- `examples/no-issues-needed.md` — CR with no findings requiring separate tracking
- `examples/partial-skip-duplicates.md` — some findings skipped due to existing issues

**Steps:**
1. Identify the source PR or code review. Run `scripts/extract-cr-comments.sh <PR>` to collect all review findings with severity, file location, and reviewer.
2. Filter findings per `references/finding-selection-criteria.md`:
   a. Include findings explicitly marked for later resolution (e.g., "will fix later", "out of scope", "follow-up").
   b. Include findings the PR author acknowledged but did not address in the current PR.
   c. Exclude findings already resolved in the PR diff.
   d. Exclude trivial or stylistic findings that do not warrant tracking.
3. For each qualifying finding, run `scripts/check-existing-issues.sh <query>` to search for existing open issues covering the same problem. Skip creation if a match is found per `references/deduplication-strategy.md`.
4. For each new finding, format the issue per `references/issue-formatting-rules.md`:
   a. Generate a concise, descriptive title from the finding summary.
   b. Build the issue body with full context: file, line, severity, reviewer comment, and link back to the PR.
   c. Assign appropriate labels based on severity and category.
5. Create each issue using `scripts/create-issue.sh <title> <body> <labels>`.
6. After all issues are created, produce a summary report listing created issues, skipped duplicates, and excluded findings with reasons.

**Output contract:** Produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Source PR | Yes | PR number and title the findings originated from |
| Total findings reviewed | Yes | Count of CR findings evaluated |
| Issues created | Yes | Count and list of created issues with links |
| Duplicates skipped | Yes | Count and list of findings skipped due to existing issues |
| Findings excluded | Yes | Count and list of findings not qualifying for issue creation, with reason |
| Confidence notes | If applicable | Caveats (e.g., uncertain duplicate match, ambiguous finding severity) |
