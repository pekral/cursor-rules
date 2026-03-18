---
name: resolve-random-jira-issue
description: "Use when resolving random JIRA issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Links PRs to JIRA issues and updates issue status."
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
- Log into JIRA, load all issues, and list only those that are to be resolved by AI (they are tagged).
  Look for tasks labeled "Resolve_by_AI." If you are supposed to search in other places as well, find those other places too. Only not resolved issues should be listed! If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
- Randomly select one and try to resolve it. Use the skill @.cursor/skills/resolve-jira-issue/SKILL.md.
