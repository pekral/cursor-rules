# Example: PR with Findings

## PR #87 — feat(orders): add bulk discount calculation

### Critical

- **`src/Services/OrderService.php:112`** — Read-modify-write on `discount_balance` without locking. Concurrent requests can apply the same discount twice. Use `SELECT ... FOR UPDATE` or an atomic decrement.

### Moderate

- **`src/Services/OrderService.php:45-62`** — Discount validation logic is duplicated from `src/Services/CouponService.php:30-47`. Extract a shared validator to eliminate the DRY violation.
- **`src/Http/Controllers/OrderController.php:78`** — Missing error handling for `calculateBulkDiscount()`. If the service throws, the user receives a 500 with a stack trace. Wrap in try/catch and return a structured error response.

### Minor

- **`src/Services/OrderService.php:20`** — Variable `$d` should use a descriptive name (e.g., `$discountRate`) per project naming conventions.

---

### Testing recommendations

- [ ] Apply a bulk discount with two concurrent sessions to verify no double-application ([link to order page](https://app.example.com/orders/new))
- [ ] Apply a discount that exceeds the remaining balance ([link to order page](https://app.example.com/orders/new))
- [ ] Verify existing single-item discount flow still works after shared validator extraction ([link to coupon page](https://app.example.com/coupons))
