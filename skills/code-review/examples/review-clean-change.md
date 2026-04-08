# Example: Clean Change Review

## Review Scope
Branch diff: `feature/add-user-avatar` vs `main`

## Summary

The change adds avatar upload functionality. Code follows project conventions, test coverage is complete, and no significant issues were identified.

## Critical Issues

None.

## Major Issues

None.

## Minor Issues

None.

## Nitpicks

### 1. Optional: extract file size constant

**Location:** `app/Actions/UploadAvatarAction.php:15`

**Problem:** Max file size `5242880` (5MB) appears inline. A named constant would improve readability.

**Suggestion:** `private const MAX_AVATAR_SIZE_BYTES = 5 * 1024 * 1024;`
