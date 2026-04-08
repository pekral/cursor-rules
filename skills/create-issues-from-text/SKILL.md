---
name: create-issues-from-text
description: "Use when breaking down a text assignment into multiple issue tracker
  issues. Analyzes the assignment, splits it into logical steps, and creates a
  separate issue for each step — written like a senior project manager would.
  Each issue contains a product summary, technical solution, and testing
  scenarios."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Never rewrite or remove the original assignment text — preserve it verbatim in the parent issue or first issue.
- Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
- All issue titles and bodies must be written in the language of the original assignment.
- Do not implement any code — this skill only creates issues.
- Each created issue must be assigned to the current user.

**Scripts:** Use the pre-built scripts in `@skills/create-issues-from-text/scripts/` to interact with the issue tracker. Do not reinvent these commands — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/get-current-user.sh` | Get the currently authenticated user login |
| `scripts/create-issue.sh <title> <body> [labels...]` | Create a single issue and assign it to the current user |
| `scripts/comment-on-issue.sh <issue> <body>` | Post a comment on an existing issue |

**References:**
- `references/issue-structure-template.md` — full issue body template with section guidelines
- `references/step-decomposition-guidelines.md` — rules for splitting assignments into steps, granularity checklist, ordering rules
- `references/title-generation-rules.md` — title pattern, examples, and anti-patterns
- `references/post-creation-checklist.md` — required and conditional steps after issue creation

**Examples:** See `examples/` for expected output formats:
- `examples/proposed-breakdown.md` — proposed breakdown presented before confirmation
- `examples/issue-single-step.md` — a single created issue with all sections filled
- `examples/breakdown-summary.md` — final summary table after all issues are created

**Steps:**
1. Read and fully understand the provided assignment text.
2. Analyze the assignment and break it down into logical, sequential implementation steps per `references/step-decomposition-guidelines.md`. Each step should represent a single deliverable unit of work (one feature, one integration, one migration, etc.).
3. For each step, prepare a complete issue draft structured per `references/issue-structure-template.md`. Generate titles per `references/title-generation-rules.md`.
4. Before creating issues, present the proposed breakdown (step titles and brief summaries) to the user for confirmation. Wait for approval before proceeding.
5. After confirmation, run `scripts/get-current-user.sh` to identify the assignee, then create all issues using `scripts/create-issue.sh`.
6. If the assignment references an existing issue or PR, link each created issue back to the source.
7. After all issues are created, complete all steps in `references/post-creation-checklist.md` — including the summary table and optional comment on the source issue using `scripts/comment-on-issue.sh`.

**Output contract:** After execution, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Issues created | Yes | Count of issues created |
| Summary table | Yes | Step number, title, URL, and dependencies for each issue |
| Assignee | Yes | GitHub login of the user all issues are assigned to |
| Source link | If applicable | Link to the original issue or PR that triggered the breakdown |
| Comment posted | If applicable | Confirmation that a summary comment was posted on the source issue |
| Confidence notes | If applicable | Caveats or assumptions (e.g., ambiguous requirements, inferred dependencies) |
