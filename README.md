<p align="center">
  <img src="assets/logo.png" alt="Cursor rules" width="280">
</p>

# Cursor Rules for PHP and Laravel

**Cursor rules for PHP and Laravel** — a complete set of `.mdc` rule files and Agent skills for Cursor, Claude Code, and Codex. One package for PHP and Laravel cursor rules: coding standards, testing, and conventions. The installer discovers the project root (via `composer.json` lookup from the current directory), mirrors the `rules/` directory into the editor's rules path and the `skills/` directory into the editor's skills path, and copies or symlinks every file into the target project. Use cursor rules for PHP and Laravel to keep every edit aligned with enforced standards, plus comprehensive Agent skills for issue resolution, bug fixing, code review, security analysis, refactoring, testing, frontend and UI, platform and data, and package review.

## Why This Package

- cursor rules for PHP and Laravel in one Composer-installable package
- unified PHP coding guidelines for PHP 8.4 projects
- Pest-based testing with mandatory code analysis and 100% coverage
- strong focus on clean code: typed properties, SRP, no redundant comments
- **59 comprehensive Agent skills** for automated workflows (v0.8.3)
- fast onboarding inside development repositories

## Installation

```bash
composer require pekral/cursor-rules --dev
vendor/bin/cursor-rules install --editor=cursor
```

The `--editor` flag is **required**. Use it to choose the target agent:

- **cursor**: `.cursor/rules`, `.cursor/skills`
- **claude**: `.claude/rules`, `.claude/skills`, and when `HOME`/`USERPROFILE` is set also `~/.claude/skills`
- **codex**: `.codex/rules`, `.codex/skills`, and when `HOME`/`USERPROFILE` is set also `~/.codex/skills`
- **all**: all of the above (Cursor, Claude, Codex in project + home)

When the package is required via Composer, sources are read from `vendor/pekral/cursor-rules/rules` and `vendor/pekral/cursor-rules/skills`.

**Important:** By default, the installer only copies missing files and keeps existing content untouched. Use the `--force` flag to overwrite existing files: `vendor/bin/cursor-rules install --force`. This is particularly useful when you want to update rules to their latest versions or when you've made local changes that should be replaced. The file `.cursor/rules/project.mdc` and `CLAUDE.md` are never overwritten once they exist in the target project, so you can safely customize them.

### Automatic Installation via Composer Plugin

By default, the Composer plugin does **not** auto-install rules on `composer install` or `composer update`. To enable automatic installation, add the following to your project's `composer.json`:

```json
{
  "extra": {
    "cursor-rules": {
      "auto-install": true,
      "editor": "claude"
    }
  }
}
```

| Option         | Description                                              | Default   |
|----------------|----------------------------------------------------------|-----------|
| `auto-install` | Enable automatic install on `composer install/update`.   | `false`   |
| `editor`       | Target editor for auto-install (`cursor`, `claude`, `codex`, `all`). | `cursor` |

If you prefer manual control, simply call `vendor/bin/cursor-rules install` in your Composer `post-update-cmd` scripts with the desired flags.

### Installing rules from GitHub (Cursor only)

You can use this repository as a **Remote Rule** in Cursor without installing the package via Composer:

1. Open **Cursor Settings** → **Rules**.
2. In the **Project Rules** section, click **Add Rule**.
3. Select **Remote Rule (Github)**.
4. Enter the repository URL: `https://github.com/pekral/cursor-rules`.

Cursor will fetch and apply the rules from the repository. Note: this method provides rules only; Agent skills are installed into your project when you use the Composer-based installation above.

### Available Commands

```bash
vendor/bin/cursor-rules help                                  # print help
vendor/bin/cursor-rules install --editor=cursor               # install for Cursor
vendor/bin/cursor-rules install --editor=claude               # install for Claude
vendor/bin/cursor-rules install --editor=codex                # install for Codex
vendor/bin/cursor-rules install --editor=all                  # install for Cursor, Claude, and Codex
vendor/bin/cursor-rules install --editor=cursor --force       # overwrite existing files
vendor/bin/cursor-rules install --editor=cursor --symlink     # prefer symlinks (fallback to copy)
vendor/bin/cursor-rules install --editor=claude --allow-bundled-scripts   # whitelist this package's bundled scripts in ~/.claude/settings.json
```

### Installer Flow

1. Determine the project root by walking up from the current directory until `composer.json` is found.
2. Resolve the rules source (local `rules/` or `vendor/pekral/cursor-rules/rules`).
3. Install rules into the target directory(ies) for the chosen editor (see `--editor`).
4. If present, resolve the skills source and install into the corresponding skill directory(ies).
5. For `--editor=claude` or `--editor=all`: copy `CLAUDE.md` to the project root (never overwrites existing).
6. Optionally overwrite existing files with `--force`; use `--symlink` to prefer symlinks (fallback to copy on Windows).
7. Surface explicit errors for missing directories, removal failures, and copy/symlink failures.

### CLI Switches

| Option            | Description                                                                 |
|-------------------|-----------------------------------------------------------------------------|
| `--editor=EDITOR`         | Target editor (required): `cursor`, `claude`, `codex`, `all`.                                                                                              |
| `--force`                 | Overwrite files that already exist in the target directory.                                                                                                 |
| `--symlink`               | Create symlinks when the OS permits; automatically falls back to copy.                                                                                      |
| `--prune`                 | Remove files in target that no longer exist in source.                                                                                                       |
| `--allow-bundled-scripts` | Opt-in. With `--editor=claude` or `--editor=all`, idempotently appends a narrow allow-list for this package's bundled scripts (`load-issue.sh` for GitHub and JIRA) to `~/.claude/settings.json`, so Claude Code stops prompting on every run. Other entries in `settings.json` are preserved. No effect when the editor target is `cursor` / `codex` or when `HOME` / `USERPROFILE` is not set. |
| *(default)*               | Only copy missing files and keep existing content untouched.                                                                                                |

---

# 🎯 Skills Overview — **v0.8.3**

> Current release includes 59 skills for issue resolution, planning, code review, refactoring, testing, performance benchmarking, security, SQL performance, frontend and UI, platform and data, content writing, and delivery workflows.

Agent skills are installed into the chosen editor’s skill directory (see `--editor`). Use `--editor=all` to install for Cursor, Claude, and Codex at once. They can be invoked when relevant. Each skill follows project conventions, ensures code quality, and maintains 100% test coverage where applicable.

> **Note:** Skill files use relative paths (e.g. `@rules/…`, `@skills/…`) without any editor-specific prefix, so they work with any supported editor (`--editor=cursor`, `--editor=claude`, `--editor=codex`, `--editor=all`).

## Issue Resolution & Delivery

| Skill | Description |
|---|---|
| `analyze-problem` | Structured problem analysis for debugging, root cause identification, and breaking down complex issues. |
| `resolve-issue` | Unified issue resolution for GitHub, JIRA, and Bugsnag. Detects the tracker from the provided link, runs `analyze-problem` before implementation, validates with tests, and creates a PR. |
| `autoresolve-oldest-github-issue` | Picks the oldest open GitHub issue (optionally filtered by label, default `Resolve_by_AI`) and chains `resolve-issue` → `code-review-github` → `process-code-review` → `merge-github-pr` against the resulting PR. Stops on any documented blocker (merge conflict, failing CI, residual Critical/Moderate findings). |
| `merge-github-pr` | Safely merge GitHub pull requests that are ready for deployment. |
| `create-issue` | Create a tracker issue from provided task text while preserving original meaning and structure. |
| `create-issues-from-text` | Batch-create issues from provided text with automatic structure detection. |
| `pr-summary` | Summarize current PR changes for development and product teams as a short two-section comment (Summary of changes + How to test), rendered as GitHub Markdown for PR comments or JIRA Wiki Markup for JIRA issue comments. |
| `skill-creator` | Scaffold a new Agent skill that follows project conventions and passes `skill-check` validation. |
| `refresh-claude-md` | Regenerate or create the project `CLAUDE.md` from the current codebase — re-detect tech stack, verified build/test commands, directory structure, and conventions while preserving every human-authored section. Adapted from the ECC `codebase-onboarding` skill; runs only when `CLAUDE.md` is stale or missing. |
| `cleanup-local-branches` | Prune dead local Git branches — those whose upstream was deleted on origin (gone) and those with no origin counterpart inactive for more than six months — while protecting the current and default branches and previewing every deletion. |
| `git-workflow` | Choose a Git branching strategy and handle merge vs rebase, conflicts, stashing, undoing mistakes, and release tagging — complements the commit/PR conventions in `@rules/git/general.mdc`, `cleanup-local-branches`, and `merge-github-pr`. |
| `product-capability` | Turn a clear-but-underspecified PRD into an engineering-ready capability plan that exposes invariants, interfaces, and unresolved decisions before any code is written, then hands off to `blueprint` or `create-issues-from-text`. |
| `blueprint` | Turn a large objective into a sequenced construction plan of 3–12 one-PR steps with dependency edges, cold-start context briefs, and exit criteria — reviewed adversarially and registered as Markdown so a fresh agent can pick up any step. |
| `autonomous-loops` | Reference catalog of loop patterns for running Claude Code autonomously — from a single sequential pipeline to multi-agent DAG orchestration — anchored to this repo's real skills with `composer build` / `composer skill-check` as the gate between iterations. |

## Code Review, Security & Architecture

| Skill | Description |
|---|---|
| `assignment-compliance-check` | Plain-language check that the PR implementation fulfills the linked issue's business requirements; reports only Critical functional gaps as a dedicated comment on the originating issue tracker — no local file written and never embedded in the PR comment. |
| `prepare-issue-context` | Pre-flight data + codebase prep before `/resolve-issue`, TDD, or CR. Extracts every assignment scenario, maps each to a code path, seeds the dev DB with the records the scenario depends on, and surfaces gaps so the implementing agent never has to guess. |
| `code-review` | Senior PHP code review focused on architecture, risk, and behavior (read-only). |
| `code-review-github` | Review GitHub pull requests; posts technical findings on the PR and a non-technical `pr-summary` comment on every linked GitHub issue (read-only on the codebase; publishes only comments via `gh`). |
| `code-review-jira` | Review changes linked to a JIRA ticket; posts technical findings on the GitHub PR and a non-technical `pr-summary` comment on the JIRA ticket (read-only on the codebase; publishes only comments via `gh` and `acli`). |
| `code-review-bugsnag` | Review a fix linked to a Bugsnag error; posts technical findings on the linked GitHub PR and a non-technical `pr-summary` comment on the Bugsnag error (read-only on the codebase; publishes only comments via `gh` and the Data Access API, needs `BUGSNAG_TOKEN`). |
| `process-code-review` | Process existing review feedback, resolve comments, and prepare next review cycle. |
| `security-review` | OWASP-focused security review (injection, auth, SSRF, exposure, misconfigurations) — read-only. |
| `api-review` | API design contract review (`@rules/api/general.mdc`) — resource-oriented REST, HTTP method/idempotence, idempotency keys, precise status codes, validation at the trust boundary. Self-scopes to API-surface diffs and runs on every CR — read-only. |
| `security-threat-analysis` | Analyze a specific security threat from a referenced source (CVE, GHSA, advisory, blog post). Produces a human-readable remediation report with ordered, agent-actionable steps and a verification check. |
| `laravel-security` | Condensed Laravel 11 / PHP 8.3 secure-defaults reference — authentication, authorization, Eloquent safety, CSRF/XSS, API security, file uploads, secrets, and production hardening. |
| `security-bounty-hunter` | Hunt for exploitable, remotely reachable vulnerabilities in a PHP/Laravel codebase for responsible disclosure — biases toward user-controlled attack paths and discards low-signal noise. |
| `penetration-tester` | Authorized, methodology-driven penetration test — runs only on an explicit pentest request against an in-scope target, validates exploitability with safe proofs of concept, and delivers a risk-rated remediation report (read-only, non-destructive). |
| `class-refactoring` | Refactor PHP classes using SOLID and Laravel best practices with testability focus. |
| `refactor-entry-point-to-action` | Refactor controller/job/command/listener entry-point logic into Action classes. |
| `smartest-project-addition` | Propose one high-impact, low-risk project improvement. |
| `understand-propose-implement-verify` | Enforce a strict 4-step loop: understand, propose, implement, verify. |
| `production-audit` | Read-only production-readiness audit from cheap local git/code/CI/config evidence — risk lenses across auth, data integrity, payments, jobs, and deployment — returning a scored ship/block verdict with specific fixes and hard caps for critical gaps. |
| `automation-audit-ops` | Evidence-first, read-only inventory of every automation in the repo (GitHub Actions, Claude Code hooks/settings, MCP servers, composer scripts, the installer, the skills catalog, scheduler) classified live/broken/redundant with keep/merge/cut/fix recommendations. |

## Testing & Quality Automation

| Skill | Description |
|---|---|
| `create-test` | Add tests following project rules with deterministic behavior and high coverage. |
| `create-missing-tests-in-pr` | Validate review recommendations and add missing tests for current PR changes. |
| `rewrite-tests-pest` | Rewrite tests to PEST style while preserving behavior and conventions. |
| `test-like-human` | Validate PR behavior from user perspective using scenario-driven testing. |
| `test-driven-development` | Enforce strict red-green-refactor flow for bugfixes and features. |
| `tester-cookbook` | Turn a JIRA task and its linked PRs into a concise QA report for a non-technical tester — focused on what to report back to the dev team, optionally with brief steps to reach the result — delivered as a JIRA Wiki Markup comment. |
| `e2e-testing` | Write or stabilize Playwright end-to-end browser tests against a Laravel app — gated on Playwright already being present, otherwise defers to manual testing or Pest/Dusk. |
| `benchmark` | Measure performance baselines and detect regressions in a Laravel app — page Core Web Vitals, API latency percentiles, build/test velocity, and DB query timing — stored as git-tracked baselines for team comparison. |
| `benchmark-optimization-loop` | Turn a vague speed goal ("make it faster", "reduce p95") into a bounded, measured optimization loop that promotes only verified, correctness-preserving wins via a baseline, a variant ledger, and a promotion gate. |

## Platform & Data

| Skill | Description |
|---|---|
| `composer-update` | Analyze composer updates, conflicts, and changelog impact. |
| `mysql-problem-solver` | Diagnose and optimize MySQL queries, indexes, and execution plans. |
| `laravel-telescope` | Analyze Laravel Telescope request data from URL, match entries in DB, and propose concrete optimizations. |
| `mysql-patterns` | Advanced MySQL patterns in Laravel — upserts, JSON columns, full-text search, partitioning, replication/read-write splitting, and deadlock handling — beyond the query tuning in `@rules/sql/optimalize.mdc`. |
| `postgres-patterns` | Advanced PostgreSQL patterns in Laravel — GIN/BRIN/partial/covering indexes, jsonb, `ON CONFLICT` upserts, `SKIP LOCKED` queue workers, cursor pagination, RLS, and `timestamptz`/`numeric` typing — the Postgres counterpart to `mysql-patterns`. |
| `redis-patterns` | Redis in Laravel — caching strategies, atomic/distributed locks, rate limiting, stampede protection, pub/sub, pipelines, and key/TTL design. |
| `docker-patterns` | Docker and docker-compose for a Laravel app — multi-stage PHP-FPM images, services (nginx, MySQL, Redis, queue worker, scheduler, Vite build), healthchecks, secrets, and image hardening. |
| `latency-critical-systems` | Latency-sensitive Laravel paths — realtime dashboards, streaming, queues, and caches — where p95 latency and data freshness matter (Octane, Horizon, Redis, read replicas). |

## Frontend & UI

| Skill | Description |
|---|---|
| `frontend-design-direction` | Choose a deliberate, polished design direction for a Laravel/Blade/Livewire/Filament interface instead of generic, templated UI. |
| `design-system` | Generate, audit, or review the visual design system of a Laravel app — Tailwind tokens, Filament theming, Blade/Livewire component consistency, and visual-polish audits. |
| `frontend-patterns` | Livewire/Blade/Alpine UI patterns — component composition, state placement, performance, forms, and loading/empty/error states. |
| `frontend-a11y` | Accessible UI in a Laravel app — semantic Blade markup, accessible forms, keyboard navigation with Alpine, focus and live-region management for Livewire, contrast, and Filament a11y. |
| `vite-patterns` | Configure or optimize Vite (laravel-vite-plugin) asset bundling — entrypoints, the `@vite` Blade directive, HMR, env vars, aliases, manifests, code splitting, and production builds. |
| `seo` | Audit, plan, or implement SEO in a Laravel app — crawlability, indexability, JSON-LD structured data in Blade, Core Web Vitals, on-page tags, keyword mapping, competitor gap analysis, E-E-A-T content quality, and measurement. |
| `frontend-slides` | Build standalone HTML/CSS/JS presentation decks — self-contained single-file decks with viewport-fit layout, keyboard navigation, and browser Print-to-PDF export. |

## Content & Writing

| Skill | Description |
|---|---|
| `article-writing` | Write long-form content (blog posts, guides, tutorials, essays, newsletters) in a distinctive voice derived from supplied examples or a default operator voice. Leads with concrete proof, bans hollow AI phrasing, and tailors structure to the medium. |
| `readme-generator` | Generate or rewrite a maintainer-ready `README.md` (and sibling root docs like `CONTRIBUTING` / `SECURITY`) from the project's actual code, manifests, scripts, and tests — a zero-hallucination scan that extracts real commands, setup steps, and configuration, committing or pushing only when explicitly asked. Adapted from the VoltAgent `readme-generator` subagent. |

---

## Claude Code Subagents

Agents are a thin orchestration layer over the existing skills — they don't replace them and they don't duplicate their prompts. The roster is named after **Greek mythology** by function (see [`docs/agents.md`](docs/agents.md)).

```text
Rules  = long-lived project standards
Skills = reusable workflows
Agents = specialised orchestration roles over multiple skills
```

| Agent | Role | Orchestrated skills |
|---|---|---|
| `argos` | All-seeing code-review gatekeeper. Reviews a PR from context or a tracker link, posts the results to the PR, and hands back a CR-done handoff. Read-only. | `code-review-github`, `code-review-jira`, `code-review-bugsnag` |
| `talos` | Tireless code-writing implementer. Implements an issue from context or a tracker link, validates with tests, opens a PR, and hands back an Impl-done handoff. Stops at the PR — never reviews or merges. | `resolve-issue` |
| `metis` | Problem-analysis advisor. Analyses a problem or a vague assignment, proposes the smallest safe solution, and publishes a reusable plan as a GitHub issue, then hands back an Analysis-done handoff. Read-only — never implements. | `analyze-problem` |
| `daidalos` | Engineering-workflow orchestrator. The entry point for a free-form request: resolves a concrete source, then routes to `metis` (analysis) or `talos` (implementation), then `argos` (review). Read-only router. | `metis`, `talos`, `argos` (routing) |

### How to use `argos` in practice

1. Install for Claude Code (or every editor):

   ```bash
   vendor/bin/cursor-rules install --editor=claude   # or --editor=all
   ```

   Agents land in `.claude/agents/`. They are **not** installed for `--editor=cursor` or `--editor=codex`.

2. Invoke it with a **source** — a GitHub PR/issue, a JIRA key, a Bugsnag error, or just the current branch/PR:

   ```text
   @argos review PR #123
   @argos review https://your.atlassian.net/browse/PROJ-42
   @argos review the current diff
   ```

3. `argos` detects the tracker, runs the matching `code-review-*` skill, lets it **post the review to the PR**, then returns a handoff: `CR done` + PR link + source link + Critical/Moderate/Minor counts + assignment-conformance verdict.

`argos` is **read-only** — it never applies fixes, commits, pushes, or merges. Those belong to separate agents.

### How to use `talos` in practice

1. Install for Claude Code (or every editor), exactly as for `argos` — agents land in `.claude/agents/` and are skipped for `--editor=cursor` / `--editor=codex`.

2. Invoke it with a **source** — a GitHub issue/PR, a JIRA key, a Bugsnag error, or just the task you want implemented:

   ```text
   @talos implement #123
   @talos implement https://your.atlassian.net/browse/PROJ-42
   @talos implement the failing upload validation
   ```

3. `talos` detects the source, runs `resolve-issue` to implement the change with tests and open a PR, then returns a handoff: `Impl done` + PR link + source link + branch + a summary of what changed and the test result.

`talos` **stops at the PR** — it never reviews its own work or merges. Hand the PR to `argos` for review next.

### How to use `metis` in practice

1. Install for Claude Code (or every editor), exactly as for `argos` / `talos` — agents land in `.claude/agents/` and are skipped for `--editor=cursor` / `--editor=codex`.

2. Invoke it with a **subject** — a GitHub issue/PR, a JIRA key, a Bugsnag error, or just a problem you want thought through:

   ```text
   @metis analyse #123
   @metis analyse https://your.atlassian.net/browse/PROJ-42
   @metis analyse why the nightly export job times out
   ```

3. `metis` runs `analyze-problem`, then returns a handoff: `Analysis done` + a link to the published plan-artifact issue + the subject link + a one-line root cause + the recommended solution.

`metis` is **read-only** — it analyses and plans, but never edits code, commits, or implements. Hand its plan issue to `talos` to build next.

### How to use `daidalos` in practice

`daidalos` is the **front door** — the agent you address with a free-form request when you don't want to pick a specialist yourself.

1. Install for Claude Code (or every editor), exactly as for the other agents.

2. Invoke it with a request — it resolves the source and chooses the route:

   ```text
   @daidalos resolve a random Resolve_by_AI issue
   @daidalos resolve https://github.com/owner/repo/issues/123
   @daidalos implement a dark-mode toggle for the settings page
   ```

3. `daidalos` resolves a concrete source, then routes: ambiguous / large work → `metis` (analysis → plan) → `talos`; clear work → `talos` directly; then `argos` for review. It returns a handoff naming the chosen route and reason.

`daidalos` is a **read-only router** — it never analyses, implements, or reviews itself, and (per the one-level subagent-nesting rule) it runs as the top-level agent you talk to, not as a nested subagent. A future top-level `zeus` will sit above it to coordinate non-engineering domains too.

---

## Rules Overview

Rules included in this package:

| File                          | Description                                                | Scope    |
|-------------------------------|------------------------------------------------------------|----------|
| `php/core-standards.mdc`      | Project context, AI behavior, and unified PHP/Laravel coding standards | Always   |
| `compound-engineering/general.mdc` | Compound engineering — make future work easier and build durable per-project compound memory | Always   |
| `git/general.mdc`             | Unified git workflow, commits, and pull request rules       | Always   |
| `code-review/general.mdc`     | Code review conventions and output rules                   | Always   |
| `code-testing/general.mdc`    | Testing conventions and quality standards                  | Always   |
| `refactoring/general.mdc`     | Shared refactoring definition (legacy → modern, incremental migration) | Refactor |
| `jira/general.mdc`            | JIRA CLI usage and formatting rules                        | JIRA     |
| `reports/general.mdc`         | Language rule for reports published to issue trackers (assignment language) | Always   |
| `laravel/architecture.mdc`    | Laravel architecture and conventions                       | Laravel  |
| `laravel/laravel.mdc`         | Laravel-specific rules and patterns                        | Laravel  |
| `laravel/filament.mdc`        | Filament v4 specific rules                                 | Filament |
| `laravel/livewire.mdc`        | Livewire component rules and conventions                   | Livewire |
| `laravel/queue-debouncing.mdc`| Safe Laravel queue debouncing, urgency separation, and replaceable work | Laravel  |
| `laravel/dynamodb.mdc`        | DynamoDB query safety: scan prevention, key-targeted reads, Tinker debug | Laravel  |
| `sql/optimalize.mdc`          | SQL query optimization, index design, schema standards     | Always   |
| `security/backend.md`         | Backend security rules and OWASP Top 10 checks             | Always   |
| `security/frontend.md`        | Frontend security rules (XSS, CSRF, CSP)                  | Frontend |
| `security/mobile.md`          | Mobile-specific security rules and WebView checks          | Mobile   |

All `.mdc` and `.md` files are ready for automatic injection by Cursor so every PHP and Laravel edit stays aligned with the enforced standards.

## Development & Testing

### Composer Scripts

```bash
composer check              # run full quality check (skill-check, normalize, phpcs, pint, rector, phpstan, audit, tests)
composer fix                # run all automatic fixes (skill-check-fix, normalize, rector, pint, phpcs)
composer build              # install (cursor-rules install --force) then fix then check
composer analyse            # run PHPStan static analysis
composer test:coverage      # run tests with 100% coverage
composer coverage           # alias for test:coverage
composer security-audit     # run security audit of dependencies
```

### Individual Commands

```bash
composer skill-check                # SKILL.md linter (validation + scoring across every skill)
composer skill-check-fix            # SKILL.md linter with auto-fix
composer composer-normalize-check   # validate composer.json normalization (dry-run)
composer composer-normalize-fix     # apply composer.json normalization
composer phpcs-check                # PHP CodeSniffer check
composer phpcs-fix                  # PHP CodeSniffer fix
composer pint-check                 # Laravel Pint check
composer pint-fix                   # Laravel Pint fix
composer rector-check               # Rector check (dry-run)
composer rector-fix                 # Rector fix
```

### Testing

```bash
./vendor/bin/pest           # run all tests
composer test:coverage      # run tests with coverage (min. 100%)
```

Remove `coverage.xml` before committing if it was produced locally.

## Author

**Petr Král** — PHP Developer & Laravel programmer, open source contributor ([pekral.cz](https://pekral.cz)).

## License

MIT — free to use, modify, and distribute.
