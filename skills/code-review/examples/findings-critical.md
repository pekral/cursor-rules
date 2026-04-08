# Example: Findings with Critical Issues

## Critical

### 1. Regression in shared helper — breaks invoice module

- **Location:** `app/Services/PriceCalculator.php:34`
- **Impact:** The changed rounding logic in `calculateTotal()` is called by `InvoiceService`, `CartService`, and `OrderService`. The new behavior truncates instead of rounding half-up, which will produce incorrect invoice totals for existing orders.
- **Fix:** Restore the original `round($amount, 2, PHP_ROUND_HALF_UP)` behavior, or introduce a separate method for the new truncation logic needed by the ticket scope.

### 2. `?array` type hint on public method

- **Location:** `app/DTOs/ReportFilters.php:12`
- **Impact:** `?array` hides the structure of the filters and breaks static analysis. Callers cannot know what keys are expected.
- **Fix:** Replace with a typed DTO:
  ```php
  public function __construct(
      public readonly ?ReportFilterData $filters,
  ) {}
  ```

## Moderate

### 3. N+1 query in loop

- **Location:** `app/Services/OrderExportService.php:55`
- **Impact:** `$order->items` is accessed inside a `foreach` loop without eager loading. For 1000 orders this produces 1001 queries.
- **Fix:** Add eager loading:
  ```php
  $orders = Order::with('items')->where(...)->cursor();
  ```

## Minor

### 4. Full mock where partial mock suffices

- **Location:** `tests/Services/OrderExportServiceTest.php:30`
- **Impact:** Full mock of `OrderRepository` prevents testing of actual query logic.
- **Fix:** Use `$this->partialMock(OrderRepository::class)` and only mock the external dependency.
