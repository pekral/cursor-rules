# API Testing Guide

## Loading API Documentation

Before testing any API endpoint, always try to load the project's API documentation first:

1. **Local docs** — look for OpenAPI/Swagger JSON/YAML, Postman collection, README API section, or dedicated API docs in the repository
2. **MCP servers or CLI tools** — if local docs are not present, use available tools to locate API reference documentation
3. **Endpoint discovery** — if no documentation can be found, use all available tools to obtain the necessary parameters for building the URL

## Executing API Tests

- Use `curl` or equivalent only when necessary
- Always include required authentication headers
- Verify that the user-visible behavior matches expectations
- Do not expose raw request/response details in the report

## Validating Responses

- Check HTTP status codes match expectations
- Verify response body structure and key fields
- Confirm error responses provide meaningful messages (without leaking internals)
- Test edge cases: missing parameters, invalid values, unauthorized access
