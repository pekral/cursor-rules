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

**Scripts:** Use the pre-built scripts in `@skills/postman-collections/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/find-postman-files.sh` | Locate existing Postman collection and environment files in the repo |
| `scripts/detect-changed-endpoints.sh [base]` | Detect changed routes, controllers, and schemas from branch diff |
| `scripts/validate-collection.sh <file>` | Validate collection JSON format, structure, and check for hard-coded secrets |

**References:**
- `references/endpoint-detection.md` — sources for detecting changed endpoints, building the change list
- `references/collection-structure.md` — file locations, folder organization, variables, environment files, JSON format
- `references/request-completeness.md` — required fields per endpoint, security rules, test assertion handling
- `references/quality-checklist.md` — full pre-finalization checklist for completeness, security, structure, validity

**Examples:** See `examples/` for expected output format:
- `examples/report-collection-updated.md` — existing collection updated with added/changed/removed endpoints
- `examples/report-new-collection.md` — new collection created from scratch
- `examples/report-validation-failed.md` — blocked by validation errors

**Steps:**
1. Run `scripts/find-postman-files.sh` to locate existing Postman assets in the repository.
2. Run `scripts/detect-changed-endpoints.sh` to detect changed endpoints from the current branch diff.
3. Build a change list grouped by resource (e.g., Users, Orders, Auth) per `references/endpoint-detection.md`.
4. If a collection exists, update it in place; if none exists, create a new collection following the project convention per `references/collection-structure.md`.
5. For each changed endpoint, ensure request completeness per `references/request-completeness.md`:
   - method and path are correct,
   - path/query variables are explicit,
   - auth type is configured (Bearer/API key/etc.) via variables,
   - required headers are included,
   - request body example matches current validation/schema,
   - at least one realistic response example is present.
6. Keep endpoint naming stable and human-readable (`[Resource] Action` style), organize folders by domain/module per `references/collection-structure.md`.
7. Add collection-level variables and environment placeholders (`baseUrl`, `token`, tenant identifiers) per `references/collection-structure.md`.
8. If tests already exist inside collection requests, update assertions only where endpoint behavior changed.
9. Run `scripts/validate-collection.sh <file>` to validate JSON format and check for hard-coded secrets.
10. Run through `references/quality-checklist.md` before finalizing.
11. Summarize what was added/changed/removed and list any TODOs for missing backend behavior.

**Output contract:** For each collection sync, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Collection file | Yes | Path to the collection file (new or updated) |
| Environment file | Yes | Path to the environment file (new, updated, or unchanged) |
| Endpoints synced | Yes | Count of added, updated, and removed endpoints |
| Changes | Yes | List of added, updated, and removed endpoints with method and path |
| Environment variables added | If applicable | New variables introduced by endpoint changes |
| Quality checklist | Yes | Pass/fail status of each checklist item |
| Validation errors | If blocked | JSON errors, hard-coded secrets, stale endpoints |
| TODOs | If applicable | Missing backend behavior or undocumented responses |
| Confidence notes | If applicable | Caveats or assumptions (e.g., assumed response shape, undocumented route) |
