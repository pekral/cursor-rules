# Example: Mixed Severity Review

## Critical Issues

### 1. Race condition in balance update

**Location:** `app/Services/WalletService.php:32`

**Problem:** Read-modify-write on `balance` without locking. Two concurrent requests can read the same balance, both subtract, and write back — resulting in only one deduction.

**Impact:** Financial loss — users can spend more than their balance.

**Fix:** Use `lockForUpdate()` or atomic `decrement()` within a transaction.

## Major Issues

### 1. N+1 query in order listing

**Location:** `app/Http/Controllers/OrderController.php:18`

**Problem:** `$orders = Order::all()` followed by accessing `$order->items` in the Blade template. Each order triggers a separate query for items.

**Impact:** Performance degrades linearly with order count — 100 orders = 101 queries.

**Fix:** `Order::with('items')->paginate(20)`

## Minor Issues

### 1. Magic number in retry logic

**Location:** `app/Jobs/SyncInventoryJob.php:15`

**Problem:** `$this->tries = 5` — the retry count is a magic number without explanation.

**Fix:** Extract to a class constant: `private const MAX_RETRIES = 5;`

## Nitpicks

### 1. Inconsistent method naming

**Location:** `app/Services/UserService.php:22`

**Problem:** Method `fetchUserData` breaks the project convention of using `get` prefix for read operations (e.g. `getUserData`).
