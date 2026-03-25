---
name: merge-github-prs
description: "Use when merging multiple GitHub pull requests that are ready for deployment."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- Never push direct changes to main branch! NEVER!
- Merge only pull requests without merge conflicts.
- Merge only pull requests with successful required checks.

**Steps:**
- Load all open pull requests from the target repository.
- For each pull request, evaluate:
- whether it can be merged cleanly (no conflicts),
- whether required checks are successful,
- whether the pull request is not in draft state.
- If a pull request fails any of these conditions, skip it and record the reason.
- For every pull request that passes all checks, apply the merge workflow from @.cursor/skills/merge-github-pr/SKILL.md.
- Merge pull requests one by one to keep history and failure handling clear.
- After each merge, verify the pull request is closed and branch cleanup is completed when allowed.
- Continue until all eligible pull requests are processed.
- Provide a final report with:
- merged pull requests,
- skipped pull requests and reasons,
- any blockers that need manual intervention.

**After completing the tasks**
- Ensure no pull request was merged with conflicts or failing checks.
- Summarize final merge results for fast release handoff.
