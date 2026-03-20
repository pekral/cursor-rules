---
name: resolve-random-github-issue
description: "Use when resolving random GitHub issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Updates GitHub issues with review results."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- I want the texts to be in the language in which the assignment was written.
- If you are not on the main git branch in the project, switch to it.

**Steps:**
- Load all open GitHub issues from the current repository and list only those that are to be resolved by AI (they are labeled).
  Look for issues labeled "Resolve_by_AI." If you cannot load the issues, find out the available tools in the system and choose the most suitable tool to download the information. Only open (not resolved) issues should be listed!
- Randomly select one and try to resolve it. Use the skill @.cursor/skills/resolve-github-issue/SKILL.md.
