# Example: All Review Points Resolved

## PR #87 — fix(orders): apply discount before tax calculation

| Field | Value |
|---|---|
| **Task** | PROJ-204 |
| **PR** | #87 |
| **Decision** | Review cycle complete |
| **CR findings** | 0 Critical, 0 Moderate |
| **Tests** | All passing |
| **Remaining blockers** | None |

### Resolved review checklist

- [x] Move discount logic before tax computation — resolved in `src/Orders/PriceCalculator.php:62`
- [x] DRY violation: extract shared rounding helper — resolved in `src/Support/MathHelper.php:15`
- [x] Add test for zero-discount edge case — resolved in `tests/Orders/PriceCalculatorTest.php:110`

### Next action

Next review cycle triggered via @skills/code-review-jira/SKILL.md. Awaiting reviewer feedback.
