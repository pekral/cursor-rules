# Example: Critical Race Condition Found

## Race Condition Review Report

### Critical -- Read-modify-write without atomicity guard

| Field | Value |
|---|---|
| **Severity** | Critical |
| **Location** | app/Services/WalletService.php:42 |
| **Pattern** | RMW without lock |
| **Risk** | Two concurrent requests can both read balance=100, both deduct 50, and both write 50 -- resulting in balance=50 instead of 0. |

**Fix:**
```php
// Instead of:
$wallet->balance -= $amount;
$wallet->save();

// Use atomic DB operation:
Wallet::where('id', $wallet->id)->decrement('balance', $amount);
// Or pessimistic lock inside transaction:
DB::transaction(function () use ($wallet, $amount) {
    $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
    $wallet->balance -= $amount;
    $wallet->save();
});
```

### Moderate -- Lock without transaction

| Field | Value |
|---|---|
| **Severity** | Moderate |
| **Location** | app/Services/InventoryService.php:88 |
| **Pattern** | lockForUpdate outside transaction |
| **Risk** | The pessimistic lock is acquired but not wrapped in a transaction, making it ineffective. Under load, concurrent reads can still interleave. |

**Fix:**
```php
// Wrap the lock in a transaction:
DB::transaction(function () use ($productId, $quantity) {
    $product = Product::where('id', $productId)->lockForUpdate()->first();
    $product->stock -= $quantity;
    $product->save();
});
```

**Summary: 1 Critical, 1 Moderate, 0 Minor**
