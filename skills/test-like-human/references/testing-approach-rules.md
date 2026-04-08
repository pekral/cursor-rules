# Testing Approach Rules

## Determining the Testing Approach

For each testing instruction extracted from the PR, determine the correct approach:

| Change type | Testing approach | Tools |
|---|---|---|
| UI interaction, form, page navigation | UI scenario | Browser MCP tools (navigation, snapshot, click, fill, wait, assert) |
| API endpoint behavior | API scenario | `curl` or equivalent |
| Backend logic (models, services, actions, jobs, commands) | Backend / code execution | `php artisan tinker` or project equivalent CLI client |
| CLI command or terminal task | CLI scenario | Terminal commands |

## Choosing Between Approaches

- If the change is **primarily backend** (models, services, actions, jobs, commands), verify behavior by executing the relevant code paths directly via `php artisan tinker` or an equivalent CLI client. Do not limit testing to the UI when a deeper verification is possible and useful.
- If the instruction involves **user interaction**, use browser MCP tools.
- If the behavior depends on **API responses**, use `curl` only when necessary.
- If the test requires **terminal interaction**, run only what is necessary.
- A single PR instruction may require **multiple approaches** (e.g., trigger via UI, then verify via tinker).

## Scenario Design

Convert each instruction into a realistic user scenario. Think like a senior tester:

- What the user tries to achieve
- What could confuse the user
- Where the flow could fail
- Whether the behavior feels correct and trustworthy
- For backend changes: does the data end up in the correct state?

## Scope Rules

- Do not invent additional requirements outside the PR instructions unless needed to verify suspicious behavior.
- Work only with the **current pull request**. Testing instructions must be taken only from the PR conversation.
- Specifically search for a section named **"Testing Recommendations"**. Prefer recommendations that include direct in-app links (full URLs) for fast click-through testing.
