---
name: resolve-github-issue
description: "Merge PRs when they are ready for deployment, one by one"
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---


**Constraint:**
- Read project.md file
- First, load all rules for the cursor editor (.cursor/rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- Never send PRs that have conflicts

**Steps:**
- Go through all PRs that have successfully completed the attached CI actions and systematically merge the changes into the main branch. 