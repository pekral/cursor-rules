---
name: resolve-random-github-issue
description: "Use when resolving random GitHub issues. Lists open issues tagged for AI resolution, randomly selects one, and delegates to resolve-github-issue skill. Fixes bugs, refactors code, performs code and security reviews, ensures 100% test coverage, runs CI checks, and creates pull requests. Updates GitHub issues with review results."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Apply @rules/github-operations.mdc
- Before resolving a task, always switch to the main branch, download the latest changes, and make sure you have the latest code in the main branch.
- If you are not on the main git branch in the project, switch to it.
- Pull request creation is mandatory for every resolved GitHub issue selected by this skill. Do not finish without a GitHub PR URL linked to the selected GitHub issue.

**Steps:**
- Load all open issues from the GitHub repository using GitHub CLI (`gh`).
  List only those issues that are to be resolved by AI (they are tagged). Look for issues labeled "Resolve_by_AI." If you are supposed to search in other places as well, find those other places too. Only open (not resolved) issues should be listed!
- Randomly select one and try to resolve it. Use the skill @skills/resolve-github-issue/SKILL.md.
- Completion is valid only when the delegated flow creates a GitHub PR and links it to the selected GitHub issue.
- Before pushing changes to PR, run @skills/code-review-github/SKILL.md for the current changes and treat it as mandatory CR.
- Fix all Critical, Moderate, and Minor findings from that CR directly in code/tests, then run @skills/code-review-github/SKILL.md again.
- Repeat the CR + fix cycle until there are no Critical, Moderate, or Minor findings left.

**After completing the tasks**
- Once the PR is created and pushed, perform a final validation pass with @skills/code-review-github/SKILL.md for the selected GitHub issue.
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!
- If the work is done, run @skills/code-review-github/SKILL.md for the current issue.
