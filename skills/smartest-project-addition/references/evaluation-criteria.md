# Evaluation Criteria

## Dimensions

Every candidate idea must be evaluated across these dimensions:

| Dimension | Description |
|---|---|
| **Impact** | How much value does this add to the project? (business, DX, reliability, performance, security) |
| **Implementation complexity** | How much effort is required? Prefer low-effort, high-value changes |
| **Risk** | What can go wrong? Consider regressions, breaking changes, data loss |
| **Reversibility** | Can this be rolled back safely if it fails? Prefer reversible changes |

## Scoring

- Rank each dimension as **high / medium / low**
- The winning proposal must have the best **impact-to-complexity-to-risk ratio**
- When two candidates tie, prefer the one with higher reversibility

## Disqualification Rules

A candidate is disqualified if:
- It requires changes across too many subsystems simultaneously (high blast radius)
- It introduces irreversible data migrations without a rollback plan
- It depends on unproven third-party services without fallback
- It duplicates functionality that already exists in the project
