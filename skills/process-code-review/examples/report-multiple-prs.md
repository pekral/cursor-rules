# Example: Multiple PRs Processed

## Task: PROJ-310

### PR #101 — refactor(auth): extract token validation

| Field | Value |
|---|---|
| **Decision** | Review cycle complete |
| **CR findings** | 0 Critical, 0 Moderate |
| **Tests** | All passing |
| **Remaining blockers** | None |

#### Resolved review checklist

- [x] Extract `TokenValidator` class — resolved in `src/Auth/TokenValidator.php`
- [x] Update tests to use new class — resolved in `tests/Auth/TokenValidatorTest.php`

#### Next action

Next review cycle triggered via @skills/code-review-github/SKILL.md.

---

### PR #102 — feat(auth): add refresh token rotation

| Field | Value |
|---|---|
| **Decision** | Blocked |
| **CR findings** | 1 Moderate remaining |
| **Tests** | All passing |
| **Remaining blockers** | Moderate finding: missing error handling for expired refresh tokens |

#### Review checklist

- [x] Implement token rotation logic — resolved in `src/Auth/RefreshTokenService.php:30`
- [ ] **MODERATE:** Add error handling for expired refresh tokens — not yet addressed

#### Next action

Fix the moderate finding and re-run the CR cycle.
