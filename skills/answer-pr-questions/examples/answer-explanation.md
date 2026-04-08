# Example: Explanation

## Reviewer Comment

> Why does `ProcessOrderAction` dispatch a job instead of processing inline?

## Response

**Answer:** The job is dispatched asynchronously because order processing involves external API calls (payment gateway, inventory service) that can take 5-15 seconds. Running this inline would block the HTTP response and risk timeouts.

**Code reference:** `app/Actions/ProcessOrderAction.php:34`

**Next step:** None — this is by design per the async processing requirement in the issue.
