# Example: Report Blocked by Environment

## Test Report — PR #115 "Add Stripe webhook handler"

### Tested Scenarios

## Scenario — Webhook Receives Payment Success

**What was tested**
Stripe sends a `payment_intent.succeeded` webhook after a test payment.

**Expected result**
The order status updates to "paid" and the user receives a confirmation email.

**Observed result**
Could not test — the Stripe webhook secret is not configured in the local environment. The endpoint returns 400 for all requests.

**Status**
Blocked

**Comment**
Environment configuration is missing. The webhook handler cannot be tested without a valid Stripe webhook secret in the local `.env` file.

---

## Scenario — Duplicate Webhook Handling

**What was tested**
The same webhook event is sent twice to verify idempotency.

**Expected result**
The second delivery is ignored without creating duplicate records.

**Observed result**
Could not test — blocked by the same environment issue as above.

**Status**
Blocked

**Comment**
Depends on the webhook endpoint working first.

---

### Overall Summary

0 of 2 scenarios could be tested. All scenarios are blocked by missing Stripe configuration in the local environment.

### Blocked Behaviors

- **All scenarios** — Stripe webhook secret not configured in local `.env`

### Recommendation

Testing is **blocked**. The developer needs to provide setup instructions or a test environment with Stripe configured before interactive testing can proceed.
