---
name: smartest-project-addition
description: "Use when you want one radically useful, high-impact project addition proposal."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Smartest Project Addition

## Purpose

Generate exactly one high-impact, actionable proposal for the most valuable addition to the current project. The proposal must be concrete, measurable, and immediately actionable.

---

## Constraint

- Apply @rules/base-constraints.mdc
- Focus on exactly one proposal with the highest impact.
- Do not return multiple alternatives in the final recommendation.
- Do not implement code unless explicitly requested.

---

## References

- `references/analysis-dimensions.md` — repository context areas to analyze and ideation prompts
- `references/evaluation-criteria.md` — scoring dimensions, ranking rules, and disqualification criteria
- `references/output-quality-checklist.md` — quality gates the final proposal must pass

---

## Examples

See `examples/` for expected output format:
- `examples/proposal-dx-improvement.md` — developer experience proposal
- `examples/proposal-reliability-improvement.md` — reliability and observability proposal
- `examples/proposal-performance-improvement.md` — performance optimization proposal

---

## Steps

1. Analyze the current repository context across all dimensions defined in `references/analysis-dimensions.md` (architecture, tests, DX, reliability, performance, security, delivery speed).
2. Use the appropriate ideation prompt from `references/analysis-dimensions.md` to generate candidate ideas.
3. Evaluate candidate ideas per `references/evaluation-criteria.md` by impact, implementation complexity, risk, and reversibility.
4. Select exactly one proposal with the best impact/complexity/risk ratio.
5. Validate the proposal against `references/output-quality-checklist.md` before producing output.
6. Produce the final output following the Output contract below.

---

## Output contract

For the selected proposal, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Proposal statement | Yes | Concise description of the proposed addition |
| Expected benefits | Yes | Business and technical benefits |
| Evaluation | Yes | Rating per dimension (impact, complexity, risk, reversibility) |
| Key risks and mitigations | Yes | Risks with specific mitigations |
| Minimal implementation plan | Yes | Smallest safe iteration, step by step |
| Test strategy | Yes | How to verify the change works |
| Rollout / Rollback | Yes | Deployment plan and revert strategy |
| Confidence notes | If applicable | Caveats, assumptions, or areas of uncertainty |

---

## After completing the task

- Ensure the recommendation is concrete, measurable, and actionable.
- Ensure the final answer contains only one top proposal.
- Verify all fields from the output contract are present.
