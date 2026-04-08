# Example: JIRA Implementation Summary Comment

The following is a correctly formatted JIRA comment using wiki markup:

```text
h3. Implementation summary

Feature is ready for review. Main behavior was validated locally.

h4. What changed
* Added validation for {{subscriber_data}} payload.
* Added guard for {{allow_resubscribe}} transition from {{2 -> 1}}.

h4. API example
{code:json}
{
  "allow_resubscribe": true,
  "subscriber_data": [
    {"email": "user@example.com", "status": 1}
  ]
}
{code}

h4. Testing recommendations
* Verify update for an existing contact.
* Verify skipped unknown contact appears in response {{errors}}.
* Verify rate-limit handling (HTTP {{429}} with {{Retry-After}}).
```
