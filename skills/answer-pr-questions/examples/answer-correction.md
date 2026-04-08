# Example: Correction

## Reviewer Comment

> This `findOrFail` will throw a 404 to the user, but the spec says we should return an empty result set instead.

## Response

**Answer:** You are right — the spec requires an empty collection, not an exception. Replaced `findOrFail` with a query that returns an empty collection when no match exists.

**Code reference:** `app/Repositories/OrderRepository.php:58`

**Next step:** Updated in the latest commit. The endpoint now returns `[]` with a 200 status when no orders match the filter.
