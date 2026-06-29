# Authorization Review — <app / PR name>

> Read-only / advise-only. A clean report means "no broken authorization found in the
> layers I checked", **not** "this app is secure". Inventory source:
> `php artisan route:list --json` (or: static `routes/*.php` fallback — confidence lowered).

## Summary
- **Critical:** <n>  ·  **Moderate:** <n>  ·  **Minor:** <n>  ·  **Covered:** <n>
- **Fix today:** <the 1–2 highest-impact exposures, one line each>

## Coverage map
Every in-scope route, with the four layers (✓ / ✗ / n/a). `policy ✗` = no centralized
policy (defense-in-depth), not "unprotected".

| Method | URI | auth | authz | scoped | policy | Lane |
|--------|-----|:----:|:-----:|:------:|:------:|------|
| GET | /orders/{order} | ✓ | ✗ | ✗ | ✗ | 🔴 Critical |
| PUT | /profile | ✓ | ✓ | ✓ | ✓ | ✅ Covered |
| GET | /login | n/a | n/a | n/a | n/a | ✅ Public-by-design |

## Findings

### 🔴 Critical — verify & fix now
**IDOR on `GET /orders/{order}`** — `OrderController@show` (`app/Http/Controllers/OrderController.php:24`) · confidence **High**
- Route has `auth` but no policy / gate, and `Order::findOrFail($id)` is not scoped to the user.
- **Evidence:** `route:list` → `OrderController@show:24` → no layer-2 / layer-3 check.
```php
public function show($id)
{
    return new OrderResource(Order::findOrFail($id)); // any user reads any order
}
```
- **Fix sketch (advice — apply by hand):** use route-model binding + `authorize('view', $order)`, or scope the query: `$request->user()->orders()->findOrFail($id)`.

### 🟡 Moderate — needs judgment (state the assumption)
**Likely IDOR on `PATCH /posts/{post}`** — `PostController@update:41` · confidence **Medium**
- Assumes `Post` is user-owned (`belongsTo(User::class)` on `Post`). If so, the bound model is not authorized.
- **Fix sketch:** add `authorize('update', $post)` or scope through `$request->user()->posts()`.

### 🔵 Minor — hardening
- `Post` has inline ownership checks but no `PostPolicy` — extract a centralized policy.

### ✅ Covered
- Summarized from the coverage map — not repeated per route.

## What I could NOT check
- <static-fallback gaps / closures / dynamically registered routes / Livewire-Filament-Nova actions / business-logic authz>
