# Scenario Design Rules

## Converting Instructions to Scenarios

Convert each testing instruction into a realistic user scenario. Think like a senior tester:

- What the user tries to achieve
- What could confuse the user
- Where the flow could fail
- Whether the behavior feels correct and trustworthy
- For backend changes: does the data end up in the correct state?

## Scope Rules

- Do not invent additional requirements outside the PR instructions unless needed to verify suspicious behavior.
- Work only with the **current pull request**. Testing instructions must be taken only from the PR conversation.
- Specifically search for a section named **"Testing Recommendations"**. Prefer recommendations that include direct in-app links (full URLs) for fast click-through testing.

## Edge Cases to Consider

- Empty states (no data, no results)
- Boundary values (min/max input lengths, date ranges)
- Error conditions (invalid input, missing required fields)
- Permission boundaries (authorized vs unauthorized access)
- Concurrent usage (multiple tabs, stale data)
