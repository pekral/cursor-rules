# Example: All Scenarios Passed

## Test Report — PR #87 "Add password reset flow"

### Tested Scenarios

## Scenario — Password Reset Email

**What was tested**
User requests a password reset from the login page.

**Expected result**
A reset email arrives within a few seconds with a working link.

**Observed result**
Email arrived in under 5 seconds. The link opened the reset form correctly.

**Status**
Passed

**Comment**
The flow is smooth and the email content is clear and professional.

---

## Scenario — Reset Link Expiry

**What was tested**
User clicks a reset link after the expiry window.

**Expected result**
The application shows a clear message that the link has expired and offers to send a new one.

**Observed result**
An expiry message appeared with a "Request new link" button that works correctly.

**Status**
Passed

**Comment**
Good user experience — no confusion about what to do next.

---

### Overall Summary

All 2 scenarios passed. The password reset flow works as expected and provides a clear, user-friendly experience throughout.

### Recommendation

The change appears ready from a user perspective.
