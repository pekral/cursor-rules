# Example: Validation Failed

## Postman Collection Sync Report

| Field | Value |
|---|---|
| **Collection** | `postman/api-collection.postman_collection.json` |
| **Environment** | `postman/local.postman_environment.json` |
| **Status** | Blocked — validation errors found |

### Validation errors

1. **Invalid JSON** — trailing comma on line 245 of collection file
2. **Missing auth variable** — `[Admin] Delete User` uses hard-coded Bearer token instead of `{{token}}` variable
3. **Stale endpoint** — `[Users] Verify Email` references `POST /api/v1/users/verify` which no longer exists in routes

### Action required

- Fix JSON syntax error before import.
- Replace hard-coded token with `{{token}}` variable reference.
- Remove stale `[Users] Verify Email` request or confirm route was re-added.
