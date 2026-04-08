# Example: New Collection Created

## Postman Collection Sync Report

| Field | Value |
|---|---|
| **Collection** | `postman/api-collection.postman_collection.json` (new) |
| **Environment** | `postman/local.postman_environment.json` (new) |
| **Endpoints synced** | 8 added, 0 updated, 0 removed |

### Changes

#### Added
- `[Auth] Login` — `POST /api/v1/auth/login`
- `[Auth] Register` — `POST /api/v1/auth/register`
- `[Auth] Logout` — `POST /api/v1/auth/logout`
- `[Auth] Refresh Token` — `POST /api/v1/auth/refresh`
- `[Orders] List Orders` — `GET /api/v1/orders`
- `[Orders] Create Order` — `POST /api/v1/orders`
- `[Orders] Get Order` — `GET /api/v1/orders/{id}`
- `[Orders] Cancel Order` — `POST /api/v1/orders/{id}/cancel`

### Environment variables
- `baseUrl` — `http://localhost:8000`
- `token` — empty placeholder
- `orderId` — placeholder for order ID in path parameters

### Quality checklist
- [x] No stale endpoints
- [x] No duplicated requests
- [x] All protected routes use variable credentials
- [x] Request examples match validation rules
- [x] Collection imports successfully

### TODOs
- `[Orders] Cancel Order` — backend returns 200 but no response body is documented yet; response example uses assumed shape
