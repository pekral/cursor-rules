---
name: laravel-telescope
description: "Use when analyzing Laravel Telescope requests from URL and DB. Loads Telescope entries, matches the same request in database tables, and proposes practical optimizations."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Purpose

Use this skill when you need to investigate a specific Laravel Telescope request, read its runtime data, find the same request directly in the Telescope database tables, and propose actionable optimizations.

The goal is practical diagnosis from real telemetry, not generic performance advice.

---

## When to use

Use this skill when the task asks for any of the following:

- analyze output from a Telescope URL
- inspect one concrete Telescope request in detail
- map a Telescope UI request to DB records
- verify request behavior from `telescope_entries` and related tables
- propose optimization opportunities based on observed request/query/job/cache/log data

---

## When not to use

Do not use this skill when:

- the project does not use Laravel Telescope
- no Telescope URL, request id, or filter context is available
- the user only wants generic Laravel performance tips without Telescope evidence

If Telescope UI or DB access is missing, continue with static analysis and clearly state limitations.

---

## Expected inputs

The skill can work with:

- Telescope URL (preferred)
- request UUID / entry UUID
- environment access (local, staging, production read-only)
- DB credentials for Telescope storage
- logs or screenshots exported from Telescope

The skill should proceed with whatever is available and not block on perfect input.

---

**Scripts:** Use the pre-built scripts in `@skills/laravel-telescope/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/fetch-entry.sh <UUID>` | Fetch a single Telescope entry by UUID |
| `scripts/fetch-family.sh <FAMILY_HASH>` | Fetch all entries sharing a family hash, with tags |
| `scripts/fetch-recent-requests.sh <FROM> <TO>` | Fetch recent request entries within a time window |

**References:**
- `references/url-parsing-rules.md` — how to parse Telescope URLs and extract identifiers, entry types, and filters
- `references/db-query-patterns.md` — SQL patterns and safety rules for querying Telescope tables
- `references/correlation-criteria.md` — how to match UI records with DB records, confidence levels
- `references/bottleneck-analysis.md` — what to look for when analyzing performance issues from Telescope data
- `references/optimization-rules.md` — rules for proposing optimizations with required impact/risk structure, behavior constraints

**Examples:** See `examples/` for expected output format:
- `examples/report-full-analysis.md` — complete analysis with UI + DB correlation
- `examples/report-limited-access.md` — analysis when DB access is unavailable
- `examples/report-n-plus-one.md` — focused N+1 query detection report

---

## Required workflow

Follow these steps in order.

### 1. Parse the Telescope target from URL

Extract environment, host, path, query params, and request identifier per `references/url-parsing-rules.md`.

### 2. Read the same request in Telescope UI data

- Inspect request metadata: method, URI, controller/action, authenticated user, response status, duration, memory, and timestamp.
- Collect related tabs when available: queries, jobs, cache operations, events, dumps, logs, exceptions.
- Build a short "request profile" before proposing any fix.

### 3. Fetch the same request directly from DB

Prefer DB-backed verification over UI-only conclusions.

Use `scripts/fetch-entry.sh <UUID>` to retrieve the entry. For related entries, use `scripts/fetch-family.sh <FAMILY_HASH>`. For time-based searches, use `scripts/fetch-recent-requests.sh <FROM> <TO>`.

For query safety rules and additional SQL patterns, see `references/db-query-patterns.md`.

### 4. Correlate UI and DB records

Confirm that the DB row is the same request shown in UI per `references/correlation-criteria.md`. If correlation is ambiguous, explicitly state what is missing.

### 5. Analyze bottlenecks from evidence

Evaluate observed data per `references/bottleneck-analysis.md`. Highlight concrete problems with supporting evidence from Telescope data.

### 6. Propose optimizations with impact and risk

For every recommendation, follow the structure defined in `references/optimization-rules.md`. Keep suggestions scoped to observed telemetry, not hypothetical architecture rewrites.

---

## Output contract

For each analyzed request, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Telescope URL | Yes | The analyzed URL or entry identifier |
| Scope / filters | Yes | Active filters affecting the analysis |
| Matched request (UI) | Yes | UUID, method + URI, status, duration/memory, timestamp |
| Matched request (DB) | If DB available | Table path, match criteria, query summary, confidence |
| Findings | Yes | Numbered list of concrete issues with evidence |
| Recommended optimizations | Yes | Each with: what, why, impact, risk, verification |
| SQL / index notes | If relevant | Index recommendations or query observations |
| Limitations | Yes | What was unavailable or uncertain |
| Confidence notes | If applicable | Caveats or assumptions affecting the analysis |

---

## Example prompts

```text
@skills/laravel-telescope/SKILL.md Analyze this Telescope URL and find the same request in DB.
```

```text
@skills/laravel-telescope/SKILL.md Compare Telescope request details with telescope_entries and propose optimizations.
```

```text
@skills/laravel-telescope/SKILL.md Investigate this slow endpoint from Telescope and produce a practical optimization plan.
```

---

## Success criteria

A good result from this skill should:

- correctly identify the target Telescope request
- correlate UI and DB records with explicit evidence
- detect meaningful performance or reliability issues
- provide prioritized, testable optimization actions
- document limitations when runtime access is incomplete

**After completing the tasks**
- If according to @skills/test-like-human/SKILL.md the changes can be tested, do it!

## Output Humanization
- Use [blader/humanizer](https://github.com/blader/humanizer) for all skill outputs to keep the text natural and human-friendly.
