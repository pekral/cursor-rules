---
name: laravel-authorization-review
description: "Use when reviewing authorization / access control in a Laravel project — find IDOR / broken object-level authorization (BOLA), audit which routes are unprotected, check policy / gate coverage, or sanity-check a new endpoint in a PR. Walks the authorization chain of every HTTP route (middleware → authorize/policy/gate → query scoping → API Resource output), anchors every finding to real `php artisan route:list --json` output plus a cited `file:line`, classifies by confidence, and produces a per-route coverage map. Read-only / advise-only — never edits code."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Laravel Authorization Review

> SAST tells you where data flows. This tells you where it flows to the **wrong user**.

This skill is the judgment layer for the one category automated scanners structurally
cannot do: **broken object-level authorization (IDOR / BOLA)** — #1 in the OWASP API
Security Top 10. Taint scanners trace untrusted *input*; they cannot decide whether
`Order::find($id)` *should* have been scoped to the current user. That is a question
about **intent**, reasoned across middleware, controller, policy, and query.

The skill is trustworthy because every finding traces to a **ground-truth anchor**:
`php artisan route:list --json` is the deterministic inventory of every endpoint and
its merged middleware. If you cannot point to both a real route **and** a cited
`file:line`, you do not report it.

---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/laravel/laravel.mdc` and `@rules/laravel/architecture.mdc`
- Apply `@rules/security/backend.md` — *Database* (authentication & authorization, least privilege) and *Safe Validation & Error Messages* (a 403-vs-404 distinction that confirms a resource exists is itself an authorization-granularity leak)
- Apply `@rules/code-review/general.mdc` — map every finding onto the CR severity scale (Critical / Moderate / Minor) so this skill plugs into a Laravel CR run
- **Advise-only.** Reads files and runs one read-only command (`php artisan route:list --json`). Never edits routes, controllers, policies, or any source; emits a report plus fix sketches for a human to apply.
- Output in English

---

## Use when
- The user wants to review authorization / access control or hunt for IDOR (broken object-level authorization)
- A Laravel CR run needs to verify that new or changed routes are scoped to their owner
- The user asks "which routes have no auth", "is this endpoint scoped to the owner", "do my controllers check policies", or "audit access control before launch"
- A PR introduces or changes routes, controllers, policies, gates, FormRequests, or API Resources

This skill complements `@skills/laravel-security/SKILL.md` (broad 7-area audit) and the
generic `@skills/security-review/SKILL.md` by going deep on the authorization chain with
a route-inventory anchor. Use it as the focused authorization lane inside a Laravel CR.

---

## Method

Work the steps in order. **Step 1 is non-negotiable: every finding traces to the real
route inventory and a cited controller line, never to a guess about what the code
"probably" does.**

### 1. Ground truth from `route:list`
1. **Confirm it's a Laravel project** — `artisan` at the repo root plus `app/` / `routes/`. If absent, stop and say so.
2. **Get the route inventory — the anchor:**
   ```bash
   php artisan route:list --json
   ```
   Returns, per route: HTTP `method`, `uri`, `name`, the full merged `middleware` list,
   and `action`. This is the spine you reason about and the source of truth for *what
   middleware actually applies* (route-group middleware is already merged in).
   > `--json` renders middleware as **resolved class strings**, not aliases:
   > `can:update,post` → `Illuminate\Auth\Middleware\Authorize:update,post`;
   > `auth:sanctum` → `Illuminate\Auth\Middleware\Authenticate:sanctum`; a custom
   > `role:admin` → its own class. **Never decide "no authz" by grepping for the literal
   > `can:`** — match the `Authorize:` / class form too, and read any custom middleware
   > class. See `references/auth-patterns.md` §2a.
3. **Scope the review** to **application routes** — skip framework / vendor / `telescope` / `horizon` / `_ignition` routes unless asked. For a **PR / diff**, intersect the route list with the changed files.
4. **Fallback when the app won't boot:** parse `routes/*.php` statically + Grep for `Route::` / middleware, **say so explicitly**, and drop every finding one confidence level. Never silently substitute a guess for the real list.
   > Laravel 11/12 boots `route:list` on defaults even with no `.env` — a missing `.env` is *not* a reliable failure trigger. Real boot failures: a missing PHP extension, or a service provider that hits the DB / cache at boot. If it's a missing extension you can enable, prefer fixing the boot (`php -d extension=gd artisan route:list --json`) over the lossy static fallback. Glob **all** of `routes/*.php` — apps split into `admin.php`, `api.base.php`, etc.

### 2. The authorization chain — four layers per route
On a large app, **establish the convention first, then audit by exception**: find how
the app authorizes (base controller `AuthorizesRequests`? policies auto-discovered or
registered? object scoping inline or in a repository / custom Builder? auth per-route or
per group?), then most routes are a fast conformance check and the findings are the
**deviations**.

For each in-scope route, record which layers are present:

| Layer | Present when (fact you can cite) | Where to look |
|-------|----------------------------------|---------------|
| **1 · Authentication** | `auth`, `auth:sanctum`, `auth:api`, or a custom guard in the route's `middleware` | `route:list` |
| **2 · Authorization** | `can:` middleware (renders as `Illuminate\Auth\Middleware\Authorize:<ability>`) **OR** `Gate::authorize`/`allows`/`denies` / `$this->authorize()` (needs `AuthorizesRequests`) / `$user->can()` **OR** `authorizeResource()` in the constructor **OR** a FormRequest whose `authorize()` returns a *real* check | `route:list` + controller / request |
| **3 · Object scoping (IDOR core)** | the record is fetched **scoped to the actor** (`$request->user()->posts()->findOrFail(...)`, scoped route binding) **OR** authorized against the bound model (`authorize('update', $post)`) | controller method |
| **4 · Policy exists** | an `App\Policies\{Model}Policy` is auto-discovered, bound via `#[UsePolicy]`, or registered with `Gate::policy(...)` (in `AppServiceProvider::boot()` on L11+, or `AuthServiceProvider::$policies` on ≤10) | filesystem + provider |

> **Laravel-version note.** Since Laravel 11 the base `Controller` is empty and does
> **not** include `AuthorizesRequests`, so `$this->authorize()` / `authorizeResource()`
> exist only when the controller adds `use AuthorizesRequests;`; the always-available
> form is `Gate::authorize(...)`. `AuthServiceProvider` is gone from the skeleton —
> policies are registered in `AppServiceProvider::boot()`, via `#[UsePolicy]`, or
> auto-discovered. Recognize **all** of these. See `references/auth-patterns.md`.

### 3. The IDOR core — object-level authorization (the flagship)
- **Implicit route-model binding resolves, it does NOT authorize.** `public function update(Post $post)` loads *anyone's* post by id. Without `authorize('update', $post)` or owner-scoping, that's IDOR — the single most common Laravel access-control bug.
- **`Model::find($request->id)` / `findOrFail($id)`** then mutate / return, with no policy call and no `where('user_id', ...)` / relationship scoping → finding, severity by how clearly the model is user-owned.
- **Actor-selected target from request input (IDOR on write — the worst case).** When the record to *write* is chosen by an id from the **request body / query** (`User::findOrFail($request->input('user_id'))->update(...)`) with no ownership check, that's a **confirmed cross-account write: High confidence even without route-model binding** — the attacker names the victim directly. Do **not** soften it to "verify".
- **Scoped bindings count:** `Route::scopeBindings()` or a nested resource scoping the child to the parent (`users.posts` resolving `$post` through `$user->posts()`) *is* object scoping — credit it.
- **Mass-assignment adjacency:** `$model->update($request->all())` where `$fillable` includes an ownership / role key (`user_id`, `team_id`, `is_admin`) is privilege escalation even when the row was scoped — note as a related finding.

To judge "should this be scoped?", look for ownership signals: `user_id` / `team_id` /
`tenant_id` columns, `belongsTo(User::class)`, a global scope, or the user's relationship
methods. **If you can't establish the model is owned, the finding is Moderate / Minor and
phrased as "verify", not asserted as a hole.**

> Ownership can be **conditional on edition / config / feature flag** — the same model
> shared-by-design in one mode and user-owned in another. Say which mode the finding
> applies to ("cross-user read **only under the Plus license**"), don't assert or dismiss flatly.

### 4. API Resource output — the second leak surface
An endpoint can authorize the *action* correctly and still **leak data in the response**.
For routes returning a `JsonResource` / `ResourceCollection` (or a raw model / `->toArray()`):
- **Field-level:** flag resources exposing ownership / internal fields — `user_id`, `email`, `*_token`, `is_admin`, password hashes, other users' PII — without a per-field guard (`when()`, `mergeWhen()`, conditional on `$request->user()`).
- **Row-level:** flag a collection endpoint returning records not scoped to the actor (`PostResource::collection(Post::all())`) — IDOR at the list level.

Confidence here is Moderate (field sensitivity is a judgment) — cite the resource
`file:line` and the exact field.

### 5. False-positive control (first-class, not a footnote)
A security tool dies from false positives. Before flagging, apply these filters — and
when in doubt, **lower the confidence, don't drop or inflate the finding**:
- **Public-by-design routes** — login, register, `password/*`, email verification, landing / public listings, and signature-verified **webhooks** are *meant* to be unauthenticated. See `references/public-by-design.md`. If a route only *looks* public, mark **"assumed public — confirm"**.
- **Authorization can live in layers you must actually check** — a missing `$this->authorize()` is *not* a finding if a `can:` middleware, `authorizeResource()`, or a FormRequest already covers it. Verify all four layers first.
- **`FormRequest::authorize()` returning `true`** is only a finding when the route is otherwise unprotected *and* touches owned data; on a public / separately-authorized route it's intentional ("verify intent").
- **Admin / global contexts** — a route behind an `admin` / role middleware legitimately queries across all users; don't flag those unscoped queries as IDOR.

### 6. Classify each finding (confidence + CR severity)
One row per finding, most severe wins. Confidence is mandatory and travels with an
evidence chain. Map onto the CR scale per `@rules/code-review/general.mdc`:

| Signal | Confidence | CR severity |
|--------|-----------|-------------|
| State-changing or owned-data route with **no auth AND no authz AND no scoping** | High — structural | **Critical** |
| `FormRequest::authorize(){ return true; }` on an otherwise-unprotected owned-data route | High | **Critical** |
| Cross-account write — record to mutate selected from request **input**, no ownership check, on an authenticated route | High | **Critical** |
| Route-model binding / `find($id)` on a likely-owned model, no `authorize()` and no owner scoping | Medium | **Moderate** |
| `Model::all()` / unscoped query, or an API Resource exposing owned / internal fields | Medium / Low | **Moderate / Minor** |
| No policy for a managed model, ad-hoc inline check instead of a policy, mass-assignment of ownership keys | Low — defense-in-depth | **Minor** |
| All applicable layers present | — | Covered ✅ |

> **Confidence rule.** *High* = you can point at the missing layer structurally (route +
> method, no check anywhere in the chain). *Medium* = the gap depends on whether the model
> is owned / the field is sensitive — state the assumption. *Low* = stylistic /
> defense-in-depth. Never present Medium / Low as a confirmed vulnerability.
>
> **Structurally broken but currently fails-closed.** Some bugs deny by default *today*
> yet hide a latent leak (e.g. `authorizeResource` mapping `index → viewAny` when the
> policy has no `viewAny`, so the endpoint 403s, while the query is `Model::all()`). Lane
> the **active** defect by its structural severity and **separately note** the latent
> unscoped query — "fails closed now, leaks the moment a permissive `viewAny` / `before` is added."

### 7. Output — coverage map + prioritized lanes
Produce the report using `templates/report.md` as the template:
1. **Summary** — counts per severity + the 1–2 things to fix today.
2. **Coverage map** — a table of **every in-scope route** → `auth ✓ · authz ✓ · scoped ✓ · policy ✓` (✓ / ✗ / n/a). Never silently omit a route you reviewed.
   > The `policy` column is **defense-in-depth, not a pass/fail gate.** When `authz` and `scoped` are satisfied *inline* the route is covered even with `policy ✗` — mark it ✗ and lane it Minor (extract a policy), not Critical.
3. **Findings by lane**, each row carrying its evidence chain (`route → Controller@method:line → missing layer` + snippet) and confidence: **Critical** (verify & fix now) → **Moderate** (needs judgment, state the assumption) → **Minor** (hardening) → **Covered** (summarized from the map).
4. For each Critical / Moderate: a **fix sketch** in Laravel idiom (add `authorize()`, scope the query through the relationship, write the policy) — as *advice for the human to apply*, not an edit.

List each route once, in its most-severe lane; the coverage map carries the rest.

### 8. Saving the report (optional, on request only)
By default **output to the conversation only**. You may offer to save to
`storage/logs/authorization-review-<YYYY-MM-DD>.md` (Laravel-native, git-ignored) — write
the file **only if the user agrees**. This report is the only file the skill may ever
write, and only when asked.

---

## Confidence & honesty
Separate the two in every report:
- **Hard facts** — the route inventory, its middleware, which files / methods / policies exist. Cite them.
- **Judgment** — whether a model is "owned", whether a field is sensitive, the fix sketch. Mark them and state the assumption.

> A clean report means "no broken authorization found in the layers I checked" — **NOT**
> "this app is secure." This skill does not cover business-logic authorization, indirect
> references it can't see in code, or the boundaries below. Never imply a clean pass is a pentest.

## Boundaries
Reviews **HTTP authorization**: routes, controllers, policies, gates, FormRequests,
Eloquent query scoping, API Resource output. Does **NOT** cover (say so when relevant):
- **Livewire / Filament / Nova / Inertia action authorization** — callable outside `route:list`, own authorization models; out of scope for this version.
- **Business-logic authorization** (approval states, "can a manager refund after 30 days") — needs domain knowledge; flag for human / pentest.
- **IDOR via indirect references not visible in code** (predictable IDs leaked elsewhere, signed-URL misuse).
- **AuthN strength** (password policy, MFA, token lifetime) and infra / gateway authz.

## Anti-patterns
- ❌ **Invent a route, policy, controller, or method** — no anchor → no finding.
- ❌ **Treat a code comment as evidence** — cite the structural reality (the missing call, the unscoped query, the route + line), never what a comment claims.
- ❌ **Flag "missing authorize()" without checking all four layers** — a `can:` middleware, `authorizeResource()`, or FormRequest may already cover it.
- ❌ **Flag a public-by-design route** (login / register / webhook / landing) for missing auth — consult `references/public-by-design.md`.
- ❌ **Assert IDOR on a model you haven't shown is owned** — Moderate / Minor "verify", not a confirmed hole.
- ❌ **Flag unscoped queries inside an admin / role-gated context** as IDOR — that's expected.
- ❌ **Present Medium / Low confidence as a confirmed vulnerability.**
- ❌ **Drop a reviewed route from the coverage map** — show every route you checked, including the covered ones.
- ❌ **Edit routes, controllers, policies, or any source**, or run state-changing commands.

---

## References
- `references/auth-patterns.md` — how Laravel authz appears across middleware, policies, gates, and `route:list --json`.
- `references/public-by-design.md` — routes that are meant to be unauthenticated.
- `templates/report.md` — the coverage-map report template.
- [Laravel Authorization docs](https://laravel.com/docs/authorization) · [OWASP API Security Top 10 — BOLA](https://owasp.org/API-Security/editions/2023/en/0xa1-broken-object-level-authorization/)

## Done when
- The route inventory was obtained (or the static fallback was used and stated), and the review was scoped to application routes / the PR diff.
- Every finding is anchored to a real route **and** a cited `file:line`, classified by confidence, and mapped to a CR severity.
- A coverage map lists every in-scope route, including the covered ones.
- Critical / Moderate findings carry a Laravel-idiom fix sketch — advice only, no code edited.
