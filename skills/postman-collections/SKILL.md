---
name: postman-collections
description: "Use when AI creates or modifies API endpoints and you need to generate or update Postman collections, keep request examples aligned with routes, and verify the collection is importable and runnable."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- All comments or outputs posted to GitHub (issues, pull requests, review comments, and PR descriptions) must be written in English.
- Never generate fake endpoints; only use endpoints that exist in code, route config, or API schema.
- Keep secrets out of collections (tokens, passwords, API keys must be variables, never hard-coded values).

**When to use:**
- The AI adds a new API endpoint.
- The AI modifies existing API endpoint path, method, headers, query params, body schema, or response examples.
- The user asks to prepare or update Postman collections for current API changes.

**Steps:**
- Detect changed endpoints from current branch diff and route/schema sources (Laravel routes, OpenAPI, API docs, controller + request classes).
- Build a change list grouped by resource (for example Users, Orders, Auth).
- Locate existing Postman assets in the repository (`postman/`, `docs/postman/`, `*.postman_collection.json`, `*.postman_environment.json`).
- If a collection exists, update it in place; if it does not exist, create a new collection in the project convention.
- For each changed endpoint, ensure:
  - method and path are correct,
  - path/query variables are explicit,
  - auth type is configured (Bearer/API key/etc.) via variables,
  - required headers are included,
  - request body example matches current validation/schema,
  - at least one realistic response example is present.
- Keep endpoint naming stable and human-readable (`[Resource] Action` style), avoid duplicates.
- Organize folders by domain/module, not by HTTP method.
- Add collection-level variables and environment placeholders:
  - `baseUrl`
  - `token` (or equivalent auth variable)
  - any required tenant/workspace/account identifiers
- If tests already exist inside collection requests, update assertions only where endpoint behavior changed.
- Validate generated JSON format (no trailing commas, valid Postman v2.1 schema shape).
- Verify collection importability and runnability using available tooling (CLI or local import check).
- Summarize what was added/changed/removed in the collection and list any TODOs for missing backend behavior.

**Quality checklist:**
- No stale endpoints remain for removed routes.
- No duplicated requests for the same method + path unless intentionally versioned.
- All protected routes use variables for credentials.
- Request examples reflect actual DTO/FormRequest validation rules.
- Collection works with a clean environment file containing placeholders only.

**Output expectations:**
- Updated or newly created Postman collection file(s).
- Updated environment file(s) if needed.
- Short changelog of endpoints synchronized with code changes.
