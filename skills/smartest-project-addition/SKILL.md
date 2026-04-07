---
name: smartest-project-addition
description: "Use when you want one radically useful, high-impact project addition proposal."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Focus on exactly one proposal with the highest impact.
- Do not return multiple alternatives in the final recommendation.
- Do not implement code unless explicitly requested.

**Steps:**
- Analyze the current repository context (architecture, tests, DX, reliability, performance, security, delivery speed).
- Use this core ideation prompt internally:
- "What is the single smartest and most radically innovative and accretive and useful and compelling addition you could make to the project at this point?"
- Use this technical variant when code-level direction is needed:
- "What is the single smartest and most radically innovative and accretive and useful and compelling technical code change/addition you could make to the project at this point?"
- Evaluate candidate ideas by impact, implementation complexity, risk, and reversibility.
- Select exactly one proposal with the best impact/complexity/risk ratio.
- Prepare output with:
- concise proposal statement,
- expected business and technical benefits,
- key risks and mitigations,
- minimal implementation plan (smallest safe iteration),
- test strategy and rollout/rollback notes.

**After completing the tasks**
- Ensure the recommendation is concrete, measurable, and actionable.
- Ensure the final answer contains only one top proposal.
