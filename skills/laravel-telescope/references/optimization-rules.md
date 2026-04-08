# Optimization Rules

## Structure for every recommendation

Each optimization must include all of the following:

| Field | Required | Description |
|---|---|---|
| What to change | Yes | Specific code, config, or infrastructure change |
| Why it helps | Yes | Causal link between the change and the observed bottleneck |
| Expected impact | Yes | Estimated effect on latency, DB load, memory, or throughput |
| Risk | Yes | Implementation risk or potential side effects |
| Verification plan | Yes | How to measure improvement after the change is applied |

## Principles

- **Scope to observed telemetry** — do not recommend changes based on hypothetical issues; every recommendation must tie back to Telescope evidence.
- **No architecture rewrites** — keep suggestions incremental and actionable; avoid suggesting complete rewrites unless the evidence overwhelmingly supports it.
- **Prioritize by impact** — order recommendations from highest to lowest expected impact.
- **Include rollback path** — for risky changes, describe how to revert if the change causes regressions.

## Behavior constraints

The skill must:
- Prefer evidence from Telescope data over assumptions
- Clearly separate confirmed findings from hypotheses
- Avoid recommending changes without measurable validation

The skill must NOT:
- Invent UUIDs, timings, or DB rows
- Claim DB correlation without explicit match criteria
- Suggest destructive DB operations without justification
