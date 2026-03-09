---
description: Create an issue in a generic issue tracker from the
  provided task description. Use when the user asks to open an issue.
  Preserve the original task content exactly and only improve formatting
  for readability.
license: MIT
metadata:
  author: Petr Král (pekral.cz)
name: create-issue
---

# Create Issue

Create a new issue in the configured issue tracker based on the provided
task description.

The issue must be **well formatted and easy to read**, but the
**original task content must never be changed**.

Only improve formatting and structure.

The issue must be created **using installed CLI tools for the issue
tracker** (for example tools for GitHub, Jira, Linear, etc.).

------------------------------------------------------------------------

# When To Use

Use this skill when:

-   the user asks to create an issue
-   a task should be tracked in an issue tracker
-   an AI agent needs to convert text into a structured issue

------------------------------------------------------------------------

# Rules

Always:

-   preserve the **original task text exactly**
-   never rewrite, summarize, or modify the content
-   only improve **formatting and structure**
-   automatically **assign the issue to the current user**
-   create the issue **using installed CLI tools available in the
    environment**
-   generate the **issue title from the first line of the task
    description**

Never:

-   change wording
-   add new requirements
-   remove information
-   revome any tasks in issue tracker!!!

------------------------------------------------------------------------

# Formatting

Improve readability using markdown formatting.

Allowed formatting changes:

-   headings
-   bullet lists
-   numbered lists
-   code blocks
-   spacing

Do not modify the meaning of the text.

------------------------------------------------------------------------

# Issue Structure

Format the issue like this:

``` markdown
# Task

<original task text>

---

# Notes

This issue was automatically formatted for readability.
Original task content was preserved exactly.
```

------------------------------------------------------------------------

# Title Generation

The **issue title must be generated from the first line of the task
description**.

Rules:

-   use the **first line exactly**
-   remove surrounding markdown formatting if present
-   keep the title concise
-   do not rewrite or summarize
-   ask for type of issue tracker (JIRA, Github, Linear etc...)

------------------------------------------------------------------------

# Creating The Issue

Use the **installed CLI tools** of the issue tracker to create the
issue.

Examples may include:

-   GitHub CLI
-   Jira CLI
-   Linear CLI
-   other installed issue tracker tools

The CLI tool should be used to:

1.  Create the issue with the generated title.
2.  Set the formatted description.
3.  Assign the issue to the current user.

------------------------------------------------------------------------

# After Creating Issue

After the issue is created:

1.  Ensure the issue is assigned to the current user.
2.  Return the **direct link to the created issue**.
