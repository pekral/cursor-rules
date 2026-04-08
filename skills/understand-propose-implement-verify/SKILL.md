---
name: understand-propose-implement-verify
description: "Use when the agent must follow a strict problem-solving loop: understand, propose, implement, verify."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Always follow this order: understand -> propose -> implement -> verify.
- Reuse existing project skills whenever they already solve a phase better than ad-hoc work.
- Never skip a phase. If a phase cannot be completed, stop and report why.

**References:**
- `references/phase-understand.md` — requirements for the Understand phase: analysis, classification, task checklist
- `references/phase-propose.md` — requirements for the Propose phase: solution design, evaluation criteria, skill selection
- `references/phase-implement.md` — requirements for the Implement phase: execution, conventions, testing
- `references/phase-verify.md` — requirements for the Verify phase: testing, regression checks, final report
- `references/skill-reuse-policy.md` — when and how to reuse existing project skills instead of ad-hoc work

**Examples:** See `examples/` for expected output format:
- `examples/report-bug-fix.md` — complete four-phase bug fix report
- `examples/report-feature-implementation.md` — complete four-phase feature implementation report
- `examples/report-blocked-by-missing-info.md` — blocked during Understand phase due to missing information

**Steps:**
1. **Understand the problem** per `references/phase-understand.md`:
   - Analyze assignment details, comments, context files, and any linked issue-tracker resources.
   - Classify the request (bug, feature, refactor, review, docs, infra).
   - Build a short task checklist with assumptions and constraints.
   - If critical information is missing, stop and request clarification.
2. **Propose a solution** per `references/phase-propose.md`:
   - Propose the smallest safe solution that satisfies the request.
   - Explain expected impact, trade-offs, risks, and why this approach is preferred.
   - Select and invoke relevant existing skills for the task (e.g., `resolve-github-issue`, `create-test`, `code-review`, `security-review`, `process-code-review`).
3. **Implement the solution** per `references/phase-implement.md`:
   - Execute the proposed solution end-to-end.
   - Keep changes focused, deterministic, and aligned with existing project conventions.
   - Add or update tests for all changed behavior.
4. **Verify correctness** per `references/phase-verify.md`:
   - Run required fixers/checkers/tests for changed scope.
   - Confirm output quality, regressions, and requirement coverage.
   - Report final status with what changed, what was tested, and any remaining risks.

**After completing the tasks:**
- Ensure every response and change can be traced back to this four-step loop.
- Ensure existing skills were reused where applicable per `references/skill-reuse-policy.md`.

**Output contract:** For each completed task, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Classification | Yes | Type of request: bug, feature, refactor, review, docs, infra |
| Scope | Yes | Affected files, modules, or services |
| Task checklist | Yes | Requirements, assumptions, open questions |
| Chosen solution | Yes | Selected approach with justification |
| Trade-offs and risks | Yes | Impact, alternatives considered, mitigations |
| Implementation summary | Yes | What was changed and which skills were invoked |
| Verification result | Yes | Test results, linter status, regression check |
| Skills used | Yes | List of project skills invoked and their contribution |
| Remaining risks | If applicable | Known caveats or unresolved concerns |
| Confidence notes | If applicable | Assumptions, uncertainties, or limitations |
| Decision | If blocked | `completed` or `blocked` with reason |
