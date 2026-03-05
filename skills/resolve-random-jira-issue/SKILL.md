---
name: resolve-random-jira-issue
description: "Picks and resolves a random JIRA issue tagged for AI resolution. Uses resolve-jira-issue skill after selection. Use when the user wants to resolve one arbitrary issue from a pool of tagged JIRA issues. Do not use when a specific JIRA issue ID or link is provided (use resolve-jira-issue instead)."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Use the same language as the assignment.
- If not on the main git branch, switch to it.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Log into JIRA and load all issues. List only those tagged for AI resolution (e.g. “Resolve_by_AI”). If the project uses other places for such tags, search those as well. List only unresolved issues.
3. Randomly select one issue and resolve it using the resolve-jira-issue skill.
