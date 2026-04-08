# Example: Bug Resolution with TDD Flow

## Issue #105 — fix(auth): login fails when email contains plus sign

| Field | Value |
|---|---|
| **Task type** | Bug |
| **Decision** | Resolved |
| **PR** | #118 |
| **Tests** | All passing, 100% coverage on changes |
| **CR findings** | None (clean after 2 iterations) |
| **CI status** | All checks passed |

### TDD steps

1. **Red:** Wrote `tests/Auth/LoginTest.php:testLoginWithPlusInEmail` — asserts that `user+tag@example.com` can authenticate. Confirmed failure: email was URL-decoded before lookup, stripping the plus sign.
2. **Green:** Fixed `src/Auth/CredentialParser.php:23` to use raw email value instead of decoded. Test passes.
3. **Refactor:** Extracted email normalization to `src/Auth/EmailNormalizer.php` per Action-pattern rules (single-use method inlined from `UserService`).

### CR cycle

- Iteration 1: 1 Moderate finding — missing test for uppercase email variant. Fixed.
- Iteration 2: Clean.

### Testing recommendations

- Log in with `user+tag@example.com` at [/login](https://app.example.com/login)
- Log in with standard email to verify no regression at [/login](https://app.example.com/login)

### Next action

PR #118 is ready for review.
