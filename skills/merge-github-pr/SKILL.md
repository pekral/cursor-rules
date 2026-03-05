---
name: merge-github-pr
description: "Merges GitHub pull requests into the main branch when they are ready for deployment. Use when the user wants to merge one or more completed PRs that have passed CI. Do not use for creating PRs, resolving issues, or merging branches with conflicts."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.
- Use the same language as the assignment.
- Do not merge PRs that have conflicts.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. Identify all PRs that have successfully completed the attached CI actions.
3. Merge changes into the main branch systematically, one PR at a time.
