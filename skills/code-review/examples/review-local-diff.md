# Example: Local Diff Review

## Review Scope
Unstaged changes in `app/Services/NotificationService.php`

## Summary

Work-in-progress changes to notification delivery. One correctness issue found.

## Critical Issues

None.

## Major Issues

### 1. Notification sent before database record is persisted

**Location:** `app/Services/NotificationService.php:42`

**Problem:** The email notification is dispatched before `$notification->save()` is called. If the save fails (validation, database error), the user receives an email for a notification that does not exist in the system.

**Impact:** User receives notification but the system has no record — support cannot investigate or resend.

**Fix:** Move `$notification->save()` before the dispatch call, or wrap both in a transaction.

## Open Questions

### 1. Is the retry logic intentional?

**Location:** `app/Services/NotificationService.php:55`

**Observation:** The `retry(3, ...)` wrapper was added but the method already runs inside a queued job with its own retry mechanism. This could cause up to 9 total attempts (3 retries × 3 job attempts). Is this intentional?

**Confidence:** Low — I do not have full context on the job configuration. Worth verifying.
