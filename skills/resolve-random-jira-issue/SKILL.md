---
name: resolve-random-jira-issue
description: "Use when resolving random JIRA issues. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Links PRs to JIRA issues."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Apply @rules/jira-operations.mdc
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- If you are not on the main git branch in the project, switch to it.
- Pull request creation is mandatory for every resolved JIRA issue selected by this skill. Do not finish without a GitHub PR URL linked to the selected JIRA issue.
- **Safe error messages:** All user-facing error and validation messages must be written so they do not reveal internal implementation details, database structure, file paths, or technology specifics that could help an attacker deduce an exploit vector. Messages should be helpful for the user but not informative for an attacker.

**Steps:**
- Log into JIRA and load all issues using the preferred JIRA tool (see @rules/jira-operations.mdc).
  List only those issues that are to be resolved by AI (they are tagged). Look for tasks labeled "Resolve_by_AI." If you are supposed to search in other places as well, find those other places too. Only not resolved issues should be listed!
- Randomly select one and try to resolve it. Use the skill @skills/resolve-jira-issue/SKILL.md.
- Completion is valid only when the delegated flow creates a GitHub PR and links it in the selected JIRA issue.
- Before creating the PR, run @skills/code-review-jira/SKILL.md for the current changes and treat it as mandatory CR.
- Fix all Critical, Moderate, and Minor findings from that CR directly in code/tests, then run @skills/code-review-jira/SKILL.md again.
- Repeat the CR + fix cycle until there are no Critical, Moderate, or Minor findings left.

**After completing the tasks**
- Once the PR is created and pushed, perform a final validation pass with @skills/code-review-jira/SKILL.md for the selected JIRA issue.
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
- If the work is done, run @skills/code-review-jira/SKILL.md for the current issue.
