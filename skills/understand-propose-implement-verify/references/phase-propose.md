# Phase: Propose

## Goal

Design the smallest safe solution that fully satisfies the requirements identified in the Understand phase.

## Required activities

- Generate at least one candidate solution
- For non-trivial tasks, generate 2-3 alternatives and compare them
- Evaluate each candidate for: correctness, impact, trade-offs, risks, reversibility
- Select the preferred approach and explain why
- Identify existing project skills that can handle parts of the work (e.g., `resolve-github-issue`, `create-test`, `code-review`, `security-review`, `process-code-review`)

## Evaluation criteria

| Criterion | Question |
|---|---|
| Scope | Is this the minimal change that satisfies the requirement? |
| Safety | Can this be reverted without side effects? |
| Conventions | Does this follow existing project patterns? |
| Testability | Can the change be verified with automated tests? |
| Skill reuse | Are existing skills being used instead of ad-hoc work? |

## Output

- Chosen solution with justification
- Expected impact and trade-offs
- Risks and mitigations
- List of skills to invoke during implementation

## Rules

- Do NOT implement anything during this phase
- Prefer the simplest approach that meets all requirements
- Always consider the "do nothing" option and explain why it was rejected
- If multiple approaches are equally viable, prefer the one with lower risk
