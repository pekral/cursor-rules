# Testing Approach Selection

## Matching Instructions to Testing Methods

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

## When Multiple Approaches Apply

A single scenario may require a combination of approaches:
1. Trigger an action via UI or API
2. Verify data state via backend CLI client
3. Confirm user-visible outcome via UI or API response
