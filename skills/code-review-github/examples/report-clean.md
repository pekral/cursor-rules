# Example: PR with No Findings

## PR #93 — fix(auth): handle expired refresh token gracefully

**No findings were identified.**

---

### Testing recommendations

- [ ] Log in, wait for the access token to expire, and verify that the refresh flow works without errors ([link to login page](https://app.example.com/login))
- [ ] Manually expire the refresh token in the database and verify the user is redirected to the login page ([link to dashboard](https://app.example.com/dashboard))
