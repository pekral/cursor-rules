# Example: High-Signal Review (Critical Issues Only)

## Critical Issues

### 1. Missing authorization check — any user can delete another user's order

**Location:** `app/Http/Controllers/OrderController.php:45`

**Problem:** The `destroy` method fetches the order by ID without verifying ownership. An authenticated user can delete any order by changing the ID in the request.

**Impact:** Data loss — any authenticated user can delete orders belonging to other users.

**Fix:**
```php
$order = Order::where('user_id', auth()->id())
    ->findOrFail($id);
```

### 2. SQL injection via raw query with concatenated user input

**Location:** `app/Repositories/ReportRepository.php:28`

**Problem:** User-supplied `$dateRange` is concatenated directly into a raw SQL query without parameterization.

**Impact:** Attacker can execute arbitrary SQL — full database compromise.

**Fix:**
```php
DB::select('SELECT * FROM reports WHERE created_at BETWEEN ? AND ?', [$start, $end]);
```
