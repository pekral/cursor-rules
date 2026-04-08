# Example: Bug Fix with TDD

## Bug: Users can submit order with negative quantity

### Step 1 -- Write failing test that reproduces the bug

```php
it('rejects order with negative quantity', function (): void {
    $product = Product::factory()->create();

    $response = $this->postJson('/api/orders', [
        'product_id' => $product->id,
        'quantity' => -5,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);
});
```

### Step 2 -- Verify test fails for the expected reason

```
$ php artisan test --filter="rejects order with negative quantity"

FAIL  Tests\Feature\OrderTest > rejects order with negative quantity
Expected status code 422, got 201.
```

The order was created with negative quantity -- confirms the bug.

### Step 3 -- Fix with minimal code

Added `'quantity' => ['required', 'integer', 'min:1']` to `StoreOrderRequest`.

### Step 4 -- Verify test passes

```
$ php artisan test --filter="rejects order with negative quantity"

PASS  Tests\Feature\OrderTest > rejects order with negative quantity
Tests: 1 passed (3 assertions)
```

### Step 5 -- Refactor if needed

No refactoring necessary. Validation rule is minimal and correct.

### Output summary

| Field | Value |
|---|---|
| **Bug** | Negative quantity accepted in orders |
| **Root cause** | Missing validation on `quantity` field |
| **Test** | `tests/Feature/OrderTest.php` -- rejects order with negative quantity |
| **Fix** | Added `min:1` validation rule to `StoreOrderRequest` |
| **All tests pass** | Yes |
