---
name: create-issue
description: "Create an issue in a generic issue tracker from the provided task description. Use when the user asks to open an issue. Preserve the original task content exactly and only improve formatting for readability."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Create Issue

Create a new issue in the configured issue tracker based on the provided task description.

The issue must be **well formatted and easy to read**, but the **original task content must never be changed**. Only improve formatting and structure.

The issue must be created **using installed CLI tools for the issue tracker** (for example tools for GitHub, Jira, Linear, etc.).

---

## When To Use

Use this skill when:

- The user asks to create an issue
- A task should be tracked in an issue tracker
- An AI agent needs to convert text into a structured issue

---

## Constraint

- Apply @rules/base-constraints.mdc
- Never modify, rewrite, summarize, or remove the original task content
- Never add new requirements that were not in the original task
- Never remove any tasks in the issue tracker
- Always assign the issue to the current user
- Ask for the type of issue tracker (GitHub, Jira, Linear, etc.) if not obvious from context

**Scripts:** Use the pre-built scripts in `@skills/create-issue/scripts/` to interact with issue trackers. Do not reinvent these commands — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/detect-tracker.sh` | Detect which issue tracker CLI tools are available |
| `scripts/create-github-issue.sh <TITLE> <BODY_FILE>` | Create a GitHub issue and assign to current user |

**References:**
- `references/content-preservation-rules.md` — what must and must not be changed in the original task
- `references/formatting-guidelines.md` — allowed formatting changes and issue body structure
- `references/title-generation-rules.md` — how to derive the issue title from the task description

**Examples:** See `examples/` for expected output format:
- `examples/issue-created-github.md` — issue created on GitHub
- `examples/issue-created-jira.md` — issue created on Jira

---

## Steps

1. Run `scripts/detect-tracker.sh` to identify available issue tracker CLI tools.
2. If the target tracker is ambiguous, ask the user which tracker to use.
3. Parse the task description:
   a. Extract the title from the first line per `references/title-generation-rules.md`.
   b. Format the body per `references/formatting-guidelines.md`, preserving all original content per `references/content-preservation-rules.md`.
4. Create the issue using the appropriate CLI tool (e.g., `scripts/create-github-issue.sh` for GitHub).
5. Ensure the issue is assigned to the current user.
6. Return the direct link to the created issue.

---

## Output Contract

For each created issue, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Tracker | Yes | Which issue tracker was used (GitHub, Jira, Linear, etc.) |
| Issue identifier | Yes | Issue number or key (e.g., #87, PROJ-451) |
| Title | Yes | The generated issue title |
| Assigned to | Yes | Who the issue was assigned to |
| Link | Yes | Direct URL to the created issue |
| Confidence notes | If applicable | Caveats (e.g., tracker detected automatically, body truncated) |
