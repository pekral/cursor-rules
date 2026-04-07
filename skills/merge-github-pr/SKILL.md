---
name: merge-github-pr
description: "Use when merging PRs that are ready for deployment, one by one."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- For all GitHub operations, prefer GitHub CLI (`gh`) as the primary tool.
- If `gh` is not available or cannot be used, use an available GitHub MCP server as fallback.
- If neither `gh` nor a GitHub MCP server is available, stop and return a failed result explaining that required GitHub tools are missing.
- Read project.mdc file
- First, load all rules for the cursor editor (.claude/rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- Never send PRs that have conflicts

**Steps:**
- For each candidate PR, load all review comments and requested changes from code review (including unresolved/outdated discussion threads) and create a checklist of required fixes.
- Verify that every checklist item from code review is fully resolved in the current PR diff.
- If at least one code review item is not resolved, DO NOT merge the PR. Instead, report unresolved items and stop processing that PR.
- Only when all code review checklist items are resolved and CI is green, continue with merge preparation.
- Go through all PRs that have successfully completed the attached CI actions and systematically merge the changes into the main branch.
