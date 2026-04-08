# Request Completeness

## Required fields per endpoint

For each changed endpoint in the collection, verify all of the following:

| Field | Requirement |
|---|---|
| Method and path | Must match the route definition exactly |
| Path/query variables | Must be explicit — no implicit or undocumented parameters |
| Auth type | Must be configured (Bearer, API key, etc.) via collection variables |
| Required headers | Must be included (Content-Type, Accept, custom headers) |
| Request body example | Must match current validation rules / schema / DTO |
| Response example | At least one realistic response example must be present |

## Security rules

- **Never generate fake endpoints** — only use endpoints that exist in code, route config, or API schema.
- **Keep secrets out of collections** — tokens, passwords, and API keys must be referenced as variables, never hard-coded values.
- All protected routes must use variables for credentials.

## Test assertions

- If tests already exist inside collection requests, update assertions **only** where endpoint behavior has changed.
- Do not remove or modify existing tests for unchanged endpoints.
- New endpoints do not require tests unless the project convention demands them.
