# Auth patterns — how Laravel authorization legitimately appears

Use this catalogue so you recognize a real check and do **not** flag a pattern you simply
did not know about. Every form below is a valid way to satisfy an authorization layer.

## 1. Authentication (layer 1)
- Route / group middleware: `auth`, `auth:web`, `auth:sanctum`, `auth:api`, or a custom guard.
- In `route:list --json` these render as `Illuminate\Auth\Middleware\Authenticate:<guard>`.
- A custom guard middleware (e.g. `EnsureTokenIsValid`) is also authentication — read the class to confirm it actually rejects unauthenticated requests.

## 2. Authorization (layer 2)
Any **one** of these satisfies the layer:
- **`can:` middleware** — `->middleware('can:update,post')`.
- **`Gate::authorize('ability', $model)` / `Gate::allows()` / `Gate::denies()`** — always available, no trait required.
- **`$this->authorize('ability', $model)`** — requires `use AuthorizesRequests;` on the controller (not in the L11+ base controller).
- **`$user->can('ability', $model)` / `$user->cannot(...)`** inside the method.
- **`authorizeResource(Model::class, 'param')`** in the controller constructor — wires the policy to every resource method.
- **A FormRequest whose `authorize()` returns a real check** (`return $this->user()->can('update', $this->route('post'));`).

### 2a. How middleware renders in `--json`
`php artisan route:list --json` resolves aliases to class strings:

| Route definition | `--json` middleware string |
|---|---|
| `can:update,post` | `Illuminate\Auth\Middleware\Authorize:update,post` |
| `auth:sanctum` | `Illuminate\Auth\Middleware\Authenticate:sanctum` |
| `role:admin` (custom / spatie) | `App\Http\Middleware\EnsureRole:admin` or the package class |

**Never** decide "no authz" by grepping for the literal `can:`. Match the `Authorize:` /
class form, and open any custom class to confirm what it enforces.

## 3. Object scoping (layer 3 — the IDOR core)
Counts as scoped when the acted-on record is bound to the actor, **not** merely resolved:
- **Relationship-scoped fetch** — `$request->user()->posts()->findOrFail($id)`.
- **`Gate::authorize('update', $post)` / `authorize('update', $post)`** against the bound model.
- **Scoped route binding** — `Route::scopeBindings()`, or a nested resource (`users.posts`) resolving `$post` through `$user->posts()`.
- **Repository / custom Eloquent Builder** that applies the tenant / owner scope centrally — credit the central scope; the controller need not repeat it.

Does **not** count: plain implicit route-model binding (`update(Post $post)`) or
`Model::find($id)` with no ownership predicate and no policy call.

## 4. Policy existence (layer 4)
Recognize all of these as a registered policy:
- An `App\Policies\{Model}Policy` **auto-discovered** by naming convention.
- Bound via the `#[UsePolicy(MyPolicy::class)]` attribute on the model (Laravel 11+).
- Registered with `Gate::policy(Model::class, MyPolicy::class)` in `AppServiceProvider::boot()` (Laravel 11+) or `AuthServiceProvider::$policies` (≤10).

A `policy ✗` in the coverage map means "no centralized policy", not "unprotected" —
inline `abort_unless($x->user_id === auth()->id())` or a `can:` middleware still covers
the route (lane it Minor: extract a policy).

## 5. Custom / package authorization
- **spatie/laravel-permission** — `role:`, `permission:`, `role_or_permission:` middleware are valid authorization layers; read the package's middleware to confirm.
- **Gate definitions in a provider** — `Gate::define('ability', fn ($user, $model) => ...)` is a real check even without a policy class.
- **`Gate::before()` / `Gate::after()`** — a `before` hook returning `true` for admins short-circuits every ability; account for it when reasoning about coverage (it can both close and *open* holes).
