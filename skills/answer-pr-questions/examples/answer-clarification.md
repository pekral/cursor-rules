# Example: Clarification

## Reviewer Comment

> This condition seems redundant — `$user->isActive()` is already checked in the middleware.

## Response

**Answer:** The middleware checks `isActive()` for authentication, but this guard checks it for a different reason — to prevent data export for users who were deactivated between the initial request and the job execution. The job runs asynchronously, so the user state can change after the middleware check passed.

**Code reference:** `app/Jobs/ExportUserDataJob.php:27`

**Next step:** None — added an inline comment to clarify this for future readers.
