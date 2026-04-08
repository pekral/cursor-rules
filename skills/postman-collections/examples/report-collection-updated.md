# Example: Collection Updated

## Postman Collection Sync Report

| Field | Value |
|---|---|
| **Collection** | `postman/api-collection.postman_collection.json` |
| **Environment** | `postman/local.postman_environment.json` |
| **Endpoints synced** | 4 added, 2 updated, 1 removed |

### Changes

#### Added
- `[Users] Create User` — `POST /api/v1/users`
- `[Users] Update User` — `PUT /api/v1/users/{id}`
- `[Users] Delete User` — `DELETE /api/v1/users/{id}`
- `[Users] Get User` — `GET /api/v1/users/{id}`

#### Updated
- `[Auth] Login` — updated request body to include `remember_me` field
- `[Auth] Register` — updated response example with new `email_verified` field

#### Removed
- `[Auth] Legacy Token Refresh` — route removed from codebase

### Environment variables added
- `userId` — placeholder for user ID in path parameters

### Quality checklist
- [x] No stale endpoints
- [x] No duplicated requests
- [x] All protected routes use variable credentials
- [x] Request examples match validation rules
- [x] Collection imports successfully

### TODOs
- None
