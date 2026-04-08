# Example: PR Comment with Findings

## Critical

- **app/Services/PaymentService.php:142** — Race condition: balance is read, modified, and written back without locking. Concurrent requests can overdraw the account. Use `lockForUpdate()` or a database-level atomic operation.
- **app/Http/Controllers/OrderController.php:87** — Regression: `calculateTotal()` now skips discount validation. This method is called by `CartController` and `InvoiceService`, which rely on discount rules. Wrap the new logic in a condition scoped to the ticket's order type.

## Moderate

- **app/Models/Order.php:34** — DRY violation: the shipping-fee calculation at lines 34-41 duplicates `ShippingService::computeFee()`. Reuse the existing service method.
- **database/migrations/2025_03_10_add_status_column.php:18** — Missing index on `orders.status` column. This column is filtered in `OrderRepository::findByStatus()` and will cause full table scans.

## Minor

- **app/Http/Requests/StoreOrderRequest.php:22** — Typo in validation message: "Oder" should be "Order".
