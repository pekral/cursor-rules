---
name: understand-propose-implement-verify
description: "Use when the agent must follow a strict problem-solving loop: understand, propose, implement, verify."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Read project.mdc file
- First, load all the rules for the cursor editor (rules/.*mdc).
- I want the texts to be in the language in which the assignment was written.
- Always follow this order: understand -> propose -> implement -> verify.
- Reuse existing project skills whenever they already solve a phase better than ad-hoc work.

**Steps:**
- **First: understand the problem**
- Analyze assignment details, comments, context files, and any linked issue-tracker resources.
- Classify the request (bug, feature, refactor, review, docs, infra).
- Build a short task checklist with assumptions and constraints.
- **Then: propose solution**
- Propose the smallest safe solution that satisfies the request.
- Explain expected impact, trade-offs, risks, and why this approach is preferred.
- Select and invoke relevant existing skills for the task (e.g. `resolve-github-issue`, `create-test`, `code-review`, `security-review`, `process-code-review`).
- **Then: implement**
- Execute the proposed solution end-to-end.
- Keep changes focused, deterministic, and aligned with existing project conventions.
- Add or update tests for all changed behavior.
- **Finally: verify correctness**
- Run required fixers/checkers/tests for changed scope.
- Confirm output quality, regressions, and requirement coverage.
- Report final status with what changed, what was tested, and any remaining risks.

**After completing the tasks**
- Ensure every response and change can be traced back to this four-step loop.
- Ensure existing skills were reused where applicable instead of duplicating logic.
