# Example: Moderate Findings

## Race Condition Review Report

### Moderate -- firstOrCreate without unique index

| Field | Value |
|---|---|
| **Severity** | Moderate |
| **Location** | app/Services/SubscriptionService.php:31 |
| **Pattern** | firstOrCreate without unique index |
| **Risk** | Two concurrent requests can both pass the "first" check and insert duplicate subscription records. Currently low traffic mitigates the risk, but this will break under scale. |

**Fix:**
```php
// Add a unique index in a migration:
Schema::table('subscriptions', function (Blueprint $table) {
    $table->unique(['user_id', 'plan_id']);
});
```

### Minor -- Missing concurrent test coverage

| Field | Value |
|---|---|
| **Severity** | Minor |
| **Location** | tests/Services/SubscriptionServiceTest.php |
| **Pattern** | No concurrency tests |
| **Risk** | Existing tests only verify single-request behavior. No tests simulate concurrent subscription creation. |

**Fix:**
Add a test that dispatches multiple concurrent subscription creation requests for the same user and plan, then asserts exactly one record exists.

**Summary: 0 Critical, 1 Moderate, 1 Minor**
