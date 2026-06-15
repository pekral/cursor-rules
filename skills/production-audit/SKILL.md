---
name: production-audit
description: "Use when asked whether a change or app is production-ready, what could break in production, or for a ship/block decision before a release — assess readiness from cheap local git, code, CI, and config evidence and return a scored verdict with specific fixes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Production Audit

## Constraints
- **Read-only skill** — never modify code, never run any git write operation (`git add`, `git commit`, `git push`, `git reset`, `git checkout -- …`, `git stash`). Reading the working tree, diff, and log is the entire job; output is the audit report only.
- **Local evidence only** — work from the local repository, git history, CI config, and committed files. Never upload repo contents, diffs, or secrets to any external service. If a deployed URL is in scope, restrict to your own HTTP/browser checks; do not exfiltrate data.
- Never print secrets. If a secret is found committed, name the file and key, not the value.
- This is a readiness audit, not a vulnerability audit — exploit-level findings belong to `@skills/security-review/SKILL.md` and `@skills/laravel-security/SKILL.md`; reference them, do not duplicate them.
- Apply `@rules/git/general.mdc` when reading the release surface, `@rules/code-review/general.mdc` for risk judgement, `@rules/laravel/laravel.mdc` and `@rules/laravel/architecture.mdc` for Laravel boundaries, `@rules/security/backend.md` for the auth/data/secret lenses, and `@rules/reports/general.mdc` for report language.

## Use when
- Someone asks "is this production-ready?", "can we ship this?", or "what could break in production?".
- A pre-release / pre-deploy gate is needed for a branch, PR, or the whole app.
- A go/no-go decision is wanted with a concrete list of blockers and fixes, fast, without external tooling.

Defer to security-review/laravel-security for deep vulnerability hunting, to `@skills/docker-patterns/SKILL.md` for Docker image correctness, and to `@skills/code-review/SKILL.md` for line-level code quality. This skill consumes their concerns as readiness signals; it does not replace them.

## Execution

### 1. Establish the release surface (git, local)
- `git status` — uncommitted or untracked work that would not ship, or worse, would.
- `git log --oneline origin/master..HEAD` (or the project default branch) — what this release actually contains.
- `git diff origin/master...HEAD --stat` then targeted `git diff` on the riskiest files — the concrete change set to judge.
- Scope the audit to this surface. A whole-app audit only when no branch/PR is named.

### 2. Review boundaries through risk lenses
Walk the change set and the boundaries it touches. For each lens, gather local evidence and record a finding only when something is missing, unsafe, or unverified.

- **Runtime & Auth** — public vs admin/authenticated routes are clearly separated (`routes/web.php`, `routes/api.php`); every protected route carries the right middleware and auth guard; authorization is enforced server-side (Policies / Gates / `authorize()` / FormRequest), not only in the UI; rate limiting (`throttle`) on auth and abuse-prone endpoints; no secrets hardcoded in source.
- **Data integrity** — every new migration is reversible (`down()` present and correct, or an explicit irreversible note); destructive changes (drop column, type narrowing, non-null without default on a populated table) have a rollout/backfill plan; Eloquent writes that can race are wrapped in transactions or use upserts; tenant/ownership scoping on multi-tenant data.
- **Payments & webhooks** — inbound webhooks verify signatures; handlers are idempotent against retries and duplicate deliveries (dedup key / `firstOrCreate`); test vs live credentials are separated and driven by config, never hardcoded.
- **Jobs & scheduling** — queued jobs are idempotent and safe to retry (`tries`, `backoff`, unique-job where needed); the scheduler (`schedule:run` / `schedule:work`) and at least one queue worker are actually wired in deployment; failed-job handling exists.
- **Config & secrets** — required env keys are documented in `.env.example`; no real secrets committed (`.env`, keys, tokens); production config is cached-safe (no closures in config), `APP_DEBUG=false` and `APP_ENV=production` for prod, `config:cache` compatible.
- **Observability** — errors and failed jobs surface somewhere (logging channel, Sentry/Bugsnag, Telescope/Horizon in non-prod or gated in prod); Horizon is configured if Redis queues are used; health/readiness endpoint exists for the deploy target.
- **Deployment & rollback** — Docker/deploy config is coherent (see `@skills/docker-patterns/SKILL.md`); `php artisan migrate --force` runs on deploy and migrations are reversible enough to roll back; a documented rollback path (previous image/tag + `migrate:rollback` or forward-fix) exists.

### 3. Check the release machinery
- **CI** — a workflow runs tests, static analysis, and the build on this branch; it is green for the head commit. A red or absent CI on critical paths is a hard signal.
- **Migrations** — they apply cleanly from a fresh DB and roll back; no migration depends on data only present in one environment.
- **Env docs** — `.env.example` matches the keys the code reads; new config has documented defaults.
- **Tests** — critical paths added/changed by this release have at least one feature/HTTP test exercising them.

### 4. Score and decide
Assign a 0–100 readiness score from the lenses and machinery, then apply hard caps.

## Output
Report in the language required by `@rules/reports/general.mdc` (canonical English when folded into a GitHub PR comment by a CR wrapper). Lead with the verdict, then the evidence. Keep it tight and actionable.

### 1. Verdict (one line)
`SHIP` / `SHIP WITH CAVEATS` / `RISKY` / `BLOCKED` — score **NN/100** — one-sentence reason.

### Score bands
- **0–49 — Blocked.** Do not ship. At least one critical readiness gap.
- **50–69 — Risky.** Beta / small rollout only, with named risks accepted by an owner.
- **70–84 — Launchable with caveats.** Ship with the listed caveats explicitly accepted.
- **85–100 — Strong.** No obvious blockers.

### Hard caps (override the raw score)
- **Cap at 69** when any of: a protected surface has no server-side authorization; a webhook/payment handler is not idempotent or skips signature verification; a migration is destructive without a rollback/backfill plan; a real secret is committed.
- **Cap at 84** when CI is red or absent on critical paths, or a critical path changed by this release has no end-to-end/feature test.

### 2. Blockers
Each: lens, location (file + line / route / migration), what is missing or unsafe, and the **concrete fix** (specific code/config change, not "add validation"). These drive the score below 70 or the relevant cap.

### 3. High-value improvements
Non-blocking gaps that materially reduce risk, each with a concrete fix.

### 4. Evidence checklist
Mark each as `pass` / `gap` / `not-checked` with the local source inspected:
- Release surface (git status / log / diff)
- Runtime & auth boundaries
- Data integrity & migration reversibility
- Payments & webhook idempotency (or `n/a`)
- Jobs & scheduler wiring (or `n/a`)
- Config & secrets / `.env.example`
- Observability & health endpoint
- Deployment & rollback path
- CI status on head commit
- Tests on critical paths

### 5. Next step
One concrete action that most raises readiness.

## Done when
- The verdict, score, and band are stated, with hard caps applied where a critical gap exists.
- Every blocker names a location and a concrete fix; the evidence checklist marks each item pass/gap/not-checked with its local source.
- No code was modified, no git write ran, and no repo data or secret left the machine.
