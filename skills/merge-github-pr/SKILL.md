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
- For each candidate PR, load all review comments and requested changes from code review (including unresolved/outdated discussion threads) and create a checklist of required fixes.
- Verify that every checklist item from code review is fully resolved in the current PR diff.
- If at least one code review item is not resolved, DO NOT merge the PR. Instead, report unresolved items and stop processing that PR.
- **CI / required checks:** Use `gh pr checks <PR>` (and, if needed, workflow run logs or the GitHub UI/API) to see whether required checks completed successfully.
- **Billing / spending-limit exception (only this case):** If GitHub Actions did **not** start jobs and the reported reason matches (or clearly paraphrases) the platform message below, treat CI as **blocked by account billing**, not as a failed quality gate — you **may** continue with merge preparation **only if** the review checklist above is fully satisfied, the PR is mergeable, and there are no conflicts. Quote the detected billing wording in your summary so the decision is auditable.
  - Canonical wording to match (substring match is enough): `The job was not started because recent account payments have failed or your spending limit needs to be increased. Please check the 'Billing & plans' section in your settings`
- **Any other non-green CI** (failed tests, linters, real workflow errors, pending checks that are not explained by the billing message above): DO NOT merge. Report failing check names and conclusions and stop processing that PR.
- When the review checklist is satisfied and either all required checks pass **or** the billing exception applies, continue with merge preparation and merge into the default branch.
- Go through all PRs that are eligible under the rules above and systematically merge the changes into the main branch.
