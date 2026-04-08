# API Testing Guide

## API Documentation Loading

Before testing any API endpoint, always try to load project API documentation first:

1. **Local docs in the repo** (preferred): OpenAPI/Swagger JSON/YAML, Postman collection, or a README/API docs section.
2. **MCP servers or CLI tools**: Use them to locate API reference documentation if local docs are absent.
3. **Discovery via MCP/other tools**: If no documentation exists, discover endpoints as needed using available tools.

## For Testing API Endpoints

- Follow steps defined in `project.mdc` section "## Testing API endpoints like human".
- Never run automatic tests from the codebase.
- Use all available tools to obtain the necessary parameters for building the URL for the API.

## API Scenario Rules

- Use `curl` only when necessary.
- Always load API documentation first if available; otherwise find endpoint information via MCP or other available tools.
- Verify that the user-visible behavior matches expectations.
- Do not expose raw request/response details in the final report.
