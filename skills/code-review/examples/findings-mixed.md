# Example: Mixed Severity Findings

## Critical

### 1. Missing authorization check on admin endpoint

- **Location:** `app/Http/Controllers/Admin/UserController.php:28`
- **Impact:** The `destroy` action has no policy or gate check. Any authenticated user can delete other users.
- **Fix:** Add authorization:
  ```php
  $this->authorize('delete', $user);
  ```

## Moderate

### 2. Plan deviation — email notification not implemented

- **Location:** (missing)
- **Impact:** The issue specifies "send confirmation email after account deletion" but no mail dispatch is present in the PR. This requirement is unmet.
- **Fix:** Implement `AccountDeletedNotification` and dispatch it from the service after successful deletion.

### 3. DRY violation — duplicated validation logic

- **Location:** `app/Http/Requests/StoreUserRequest.php:18` and `app/Http/Requests/UpdateUserRequest.php:22`
- **Impact:** Both request classes define identical email and name validation rules. Changes to one will be forgotten in the other.
- **Fix:** Extract shared rules into a trait or base request class:
  ```php
  trait UserValidationRules
  {
      protected function userRules(): array
      {
          return [
              'email' => ['required', 'email', 'max:255'],
              'name' => ['required', 'string', 'max:100'],
          ];
      }
  }
  ```

### 4. Array key type safety risk

- **Location:** `app/Services/ImportService.php:42`
- **Impact:** `$grouped[$record->external_id][] = $record;` — if `external_id` contains decimal integer strings like `'123'`, PHP casts the key to `int`, causing unexpected reindexing when `array_merge()` is called on line 58.
- **Fix:** Use a typed collection or enforce string keys explicitly:
  ```php
  $key = 'id_' . $record->external_id;
  $grouped[$key][] = $record;
  ```

## Minor

### 5. Missing edge-case test

- **Location:** `tests/Services/ImportServiceTest.php`
- **Impact:** No test covers the scenario where `external_id` is `null`. This would produce a PHP warning and silent data loss.
- **Fix:** Add test: `Unit: ImportService with null external_id should throw ValidationException`.
