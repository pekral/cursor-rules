# Endpoint Detection

## Sources for detecting changed endpoints

Detect changed endpoints by comparing the current branch diff against these sources, in priority order:

1. **Route definitions** — Laravel `routes/*.php`, Express router files, or framework-equivalent route registration files
2. **OpenAPI / Swagger schema** — `openapi.yaml`, `openapi.json`, `swagger.json`, or files under `docs/api/`
3. **Controller + request classes** — controllers, form requests, DTOs, and validation rule files that define endpoint behavior
4. **API documentation** — any in-repo API docs that describe endpoints

## Building the change list

- Group detected changes by **resource** (e.g., Users, Orders, Auth), not by HTTP method.
- For each changed endpoint, record: HTTP method, path, what changed (path, method, headers, query params, body schema, response examples).
- Include newly added endpoints and flag removed endpoints for cleanup.

## What counts as a changed endpoint

An endpoint is considered changed when any of the following differ from the previous state:
- Route path or HTTP method
- Required or optional headers
- Query parameters (added, removed, renamed)
- Request body schema or validation rules
- Response shape or status codes
- Authentication requirements
