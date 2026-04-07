---
name: merge-github-pr
description: "Use when merging PRs that are ready for deployment, one by one."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Never send PRs that have conflicts

**Steps:**
- For each candidate PR, load all review comments and requested changes from code review (including unresolved/outdated discussion threads) and create a checklist of required fixes.
- Verify that every checklist item from code review is fully resolved in the current PR diff.
- If at least one code review item is not resolved, DO NOT merge the PR. Instead, report unresolved items and stop processing that PR.
- Only when all code review checklist items are resolved and CI is green, continue with merge preparation.
- Go through all PRs that have successfully completed the attached CI actions and systematically merge the changes into the main branch.
