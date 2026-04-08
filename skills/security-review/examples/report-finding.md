# Example: Single Finding

## Issue: Missing Authorization Check

| Field | Value |
|---|---|
| **Severity** | High |
| **Category** | A01 Broken Access Control / BOLA |
| **Location** | `app/Http/Controllers/PostController.php:34` |

### Problem

The controller fetches a model by ID without verifying ownership.

### Exploit Scenario

An authenticated user can access another user's resource by changing the ID parameter in the request URL (e.g., `/api/posts/42` to `/api/posts/43`).

### Recommended Fix

Use policy check or scoped query to enforce ownership.

### Refactored Example

```php
$post = Post::where('user_id', auth()->id())
    ->findOrFail($id);
```
