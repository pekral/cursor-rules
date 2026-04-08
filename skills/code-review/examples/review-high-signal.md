# Example: High-Signal Review (Critical Issues Only)

## Review Scope
Branch diff: `feature/payment-refund` vs `main`

## Critical Issues

### 1. Refund amount not validated — negative values accepted

**Location:** `app/Actions/ProcessRefundAction.php:22`

**Problem:** The `$amount` parameter is passed directly to the payment gateway without checking for negative or zero values. A negative refund would charge the customer instead.

**Impact:** Financial — customers could be charged instead of refunded.

**Fix:**
```php
if ($amount <= 0) {
    throw new InvalidArgumentException('Refund amount must be positive.');
}
```

### 2. Missing database transaction — partial refund state on failure

**Location:** `app/Actions/ProcessRefundAction.php:35`

**Problem:** The order status is updated to "refunded" before the payment gateway call succeeds. If the gateway call fails, the order shows as refunded but the customer never received the money.

**Impact:** Data integrity — order status diverges from actual payment state.

**Fix:** Wrap both operations in a database transaction and update status only after gateway confirmation.
