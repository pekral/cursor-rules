# Public-by-design routes — meant to be unauthenticated

Do **not** flag these for "missing auth". If a route only *looks* public, mark it
**"assumed public — confirm"** rather than asserting a hole.

## Authentication & account flows
- `login`, `register`, `logout` (logout may be auth-only — check).
- `password/*` — forgot-password, reset-password request and submit.
- Email verification request links (the *signed* verify route itself is protected by Laravel's `signed` middleware — credit it).
- OAuth / social-login callback routes (`auth/{provider}/callback`).

## Public content
- Home / landing pages, marketing pages, public blog / docs, public product listings.
- Public read-only API endpoints that intentionally expose non-owned data (a public price list, a status page) — confirm the data is genuinely non-owned before clearing.

## Webhooks & machine-to-machine
- Signature-verified **webhooks** — Stripe, GitHub, Paddle, etc., typically behind a `verifyWebhookSignature`-style middleware or verifying the signature inside the handler. The signature **is** the authorization; do not flag for missing `auth`. Verify the signature check actually exists in the middleware or handler.
- Health / readiness probes (`up`, `/health`) — framework or infra routes, out of scope.

## Framework / vendor routes (out of scope unless asked)
- `telescope/*`, `horizon/*`, `_ignition/*`, `sanctum/csrf-cookie`, `storage/*`, Livewire's `livewire/*` update endpoint.

## How to confirm "public"
- The route is in this list **and** returns no owned / per-user data, **or**
- An explicit business reason in the issue / PR says it is public, **or**
- The handler verifies an alternative credential (webhook signature, signed URL, API key middleware).

If none of these hold, treat the unauthenticated route as a finding — but lane it by how
clearly it touches owned or state-changing data.
