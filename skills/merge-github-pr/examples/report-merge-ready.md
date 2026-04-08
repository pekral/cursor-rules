# Example: PR Ready to Merge

## PR #142 — fix(auth): validate token expiry before refresh

| Field | Value |
|---|---|
| **Decision** | `merge` |
| **CI** | All checks passed |
| **Conflicts** | None |
| **Reviews** | 1 approved, 0 changes requested |
| **Unresolved threads** | 0 |

### Review checklist

- [x] Token expiry check added before refresh call — resolved in `src/Auth/TokenService.php:45`
- [x] Test for expired token scenario — resolved in `tests/Auth/TokenServiceTest.php:89`

### Next action

Rebase-merge via `scripts/merge-pr.sh 142`.
