---
name: resolve-random-jira-issue
description: "Use when resolving random JIRA issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Links PRs to JIRA issues and updates issue status."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- For all GitHub operations, prefer GitHub CLI (`gh`) as the primary tool.
- If `gh` is not available or cannot be used, use an available GitHub MCP server as fallback.
- If neither `gh` nor a GitHub MCP server is available, stop and return a failed result explaining that required GitHub tools are missing.
- Read project.mdc file
- First, load all the rules for the cursor editor (rules/.*mdc).
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- I want the texts to be in the language in which the assignment was written.
- If you are not on the main git branch in the project, switch to it.
- For comments posted to JIRA, always use JIRA Wiki Markup (not Markdown) and follow the universal structure from JIRA-focused skills.
- Pull request creation is mandatory for every resolved JIRA issue selected by this skill. Do not finish without a GitHub PR URL linked to the selected JIRA issue.

**Steps:**
- Log into JIRA and load all issues using the acli console tool first. If acli is not available, use the JIRA MCP server if available. If neither is available, stop and display a message stating that at least one of these tools must be installed to use the skill.
  List only those issues that are to be resolved by AI (they are tagged). Look for tasks labeled "Resolve_by_AI." If you are supposed to search in other places as well, find those other places too. Only not resolved issues should be listed!
- Randomly select one and try to resolve it. Use the skill @skills/resolve-jira-issue/SKILL.md.
- Completion is valid only when the delegated flow creates a GitHub PR and links it in the selected JIRA issue.
