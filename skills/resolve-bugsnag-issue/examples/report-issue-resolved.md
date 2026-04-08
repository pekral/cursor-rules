# Example: Bugsnag Issue Resolved

## Bugsnag Issue BSN-4821 — NullPointerException in OrderService::calculateTotal

| Field | Value |
|---|---|
| **Decision** | `resolved` |
| **Bugsnag ID** | BSN-4821 |
| **PR** | #287 — fix(orders): handle null line items in total calculation |
| **Root cause** | `OrderService::calculateTotal` did not guard against null `lineItems` collection when order was in draft state |
| **TDD status** | Red-green cycle completed |
| **Code review** | Clean — no Critical or Moderate findings |
| **CI status** | All checks passed |
| **Coverage** | 100% of changed lines covered |

### Fix summary

- Added null guard in `src/Services/OrderService.php:67`
- Returns zero total for orders with no line items instead of throwing

### Tests added

- `tests/Services/OrderServiceTest.php:testCalculateTotalWithNullLineItems` — reproduces the original failure
- `tests/Services/OrderServiceTest.php:testCalculateTotalWithEmptyLineItems` — edge case coverage

### Testing recommendations

- Verify order creation flow with empty cart: https://app.example.com/orders/new
- Verify existing orders display correctly: https://app.example.com/orders

### Confidence notes

- Fix is minimal and isolated to the null guard; no side effects on existing order flows.

### Next action

PR #287 is ready for human review and QA testing.
