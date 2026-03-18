---
name: merge-github-pr
description: "Use when merging PRs that are ready for deployment, one by one."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all rules for the cursor editor (.cursor/rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- Never send PRs that have conflicts

**Steps:**
- Go through all PRs that have successfully completed the attached CI actions and systematically merge the changes into the main branch.
