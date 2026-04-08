# Example: Disagreement

## Reviewer Comment

> This should use a database transaction instead of dispatching separate events.

## Response

**Answer:** A transaction would guarantee atomicity, but in this case the two operations target different bounded contexts (billing and notifications). Wrapping them in a single transaction would couple these domains and make the notification failure roll back the billing record, which is not the desired behavior.

**Reasoning:** The current approach uses eventual consistency — if the notification event fails, it retries independently without affecting the billing state. This matches the architecture decision documented in the issue.

**Next step:** If you see a scenario where this could cause data inconsistency, happy to discuss a specific case.
