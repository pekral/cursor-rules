# Collection Structure

## File locations

Locate existing Postman assets in the repository by searching for:
- `postman/` directory
- `docs/postman/` directory
- `*.postman_collection.json` files
- `*.postman_environment.json` files

If a collection exists, update it in place. If none exists, create a new collection following the project convention.

## Folder organization

- Organize folders by **domain/module** (e.g., Auth, Users, Orders), NOT by HTTP method.
- Keep endpoint naming stable and human-readable using `[Resource] Action` style (e.g., `[Users] Create User`, `[Auth] Login`).
- Avoid duplicate requests for the same method + path unless intentionally versioned.

## Collection-level variables and environment placeholders

Every collection must include these variables:
- `baseUrl` — the API base URL
- `token` (or equivalent auth variable) — authentication credential placeholder
- Any required tenant/workspace/account identifiers specific to the project

## Environment files

- Environment files must contain **placeholders only** — never real credentials or secrets.
- The collection must work with a clean environment file containing only placeholder values.
- Update environment files when new variables are introduced by endpoint changes.

## JSON format requirements

- Valid Postman Collection v2.1 schema shape.
- No trailing commas.
- Properly escaped strings.
- Valid JSON that passes `jq .` without errors.
