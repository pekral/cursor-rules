# Example: Mixed Severity Review

## Review Scope
Branch diff: `feature/user-export` vs `main`

## Critical Issues

### 1. Unbounded query loads all users into memory

**Location:** `app/Actions/ExportUsersAction.php:18`

**Problem:** `User::all()` loads every user into memory. With 500K+ users this will exhaust PHP memory and crash the process.

**Impact:** Application crash on production with large datasets.

**Fix:** Use `User::cursor()` for streaming or `chunk(500)` for batch processing.

## Major Issues

### 1. Missing authorization check on export endpoint

**Location:** `app/Http/Controllers/ExportController.php:12`

**Problem:** Any authenticated user can trigger the export. There is no permission check to verify the user has admin or export privileges.

**Impact:** Unauthorized data access — regular users can export all user data.

**Fix:** Add policy check: `$this->authorize('export', User::class);`

## Minor Issues

### 1. Magic number in chunk size

**Location:** `app/Actions/ExportUsersAction.php:25`

**Problem:** `->chunk(1000)` uses a hardcoded number without explanation.

**Fix:** Extract to a class constant: `private const EXPORT_CHUNK_SIZE = 1000;`

## Nitpicks

### 1. Method could use more descriptive name

**Location:** `app/Actions/ExportUsersAction.php:10`

**Problem:** Method `run()` does not describe what it does. Consider `exportToCsv()` for clarity.
