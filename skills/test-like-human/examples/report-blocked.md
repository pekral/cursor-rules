# Example: Report with Blocked Scenario

## Test Report — PR #201 "Add two-factor authentication"

### Tested Scenarios

## Scenario — Enable 2FA from Settings

**What was tested**
User enables two-factor authentication from the account settings page.

**Expected result**
A QR code is displayed that can be scanned with an authenticator app, followed by a confirmation step.

**Observed result**
The settings page returned a server error when clicking "Enable 2FA". The feature could not be tested.

**Status**
Blocked

**Comment**
The feature is inaccessible due to a server error. Testing cannot proceed until this is resolved.

---

## Scenario — Login with 2FA Code

**What was tested**
User logs in and is prompted for a 2FA code after entering credentials.

**Expected result**
A code input field appears after successful password entry.

**Observed result**
Could not be tested because enabling 2FA was blocked (see previous scenario).

**Status**
Blocked

**Comment**
Depends on the "Enable 2FA" scenario which is currently broken.

---

### Overall Summary

0 of 2 scenarios could be tested. All scenarios were blocked due to a server error when enabling 2FA.

### Blocked Scenarios

1. **Enable 2FA from Settings** — server error prevents access to the feature.
2. **Login with 2FA Code** — depends on enabling 2FA, which is blocked.

### Recommendation

The change cannot be evaluated from a user perspective. The server error must be fixed first.
