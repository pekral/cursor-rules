---
name: automation-audit-ops
description: "Use when you need an evidence-first, read-only inventory of every automation in this repo (GitHub Actions, Claude Code hooks/settings, MCP servers, composer scripts, the bundled CLI installer, the skills catalog, scheduler/cron) before changing any of them, classifying each as live, broken, or redundant and recommending keep/merge/cut/fix."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/reports/general.mdc` — any report published to an issue tracker follows the assignment language; code identifiers, file paths, workflow names, and command output stay verbatim.
- Apply `@rules/compound-engineering/general.mdc` — a recurring breakage or a confirmed redundancy is a durable lesson; recommend recording it in the project's own memory, never in this shared package.
- Apply `@rules/git/general.mdc` — use `gh` for GitHub Actions run history; if no GitHub tool is available, stop and report that, do not guess run state.
- **Read-only skill** — never modify, stage, commit, push, run installers, trigger workflows, or change any setting. Reading the working tree, run logs, and config is allowed; mutating anything is not. The only output is the inventory plus the keep/merge/cut/fix report.
- **Evidence-first** — never claim an automation is live, authenticated, or working just because a config or skill references it. Every claim cites proof: a file path with line, a workflow run id/conclusion, a log line, or the exact command output that produced it.
- **Flag ambiguity, do not assume.** When proof is missing or contradictory, mark the item `UNVERIFIED` and state what would confirm it — never upgrade a guess to a fact.
- Do not invent automations. Catalog only surfaces that actually exist in the repo or the configured environment.

## Use when
- You are about to add, rewrite, or delete an automation and need to know what already exists and whether it works.
- CI, a hook, an MCP server, or a composer script is suspected broken, stale, or duplicated and you need an evidence-backed verdict before touching it.
- The automation surface has grown organically and you want a keep/merge/cut/fix decision set across the whole repo.

## Execution
Number each finding and carry its proof through to the report. Work surface by surface.

1. **Enumerate the surfaces.** Build the raw catalog from these locations (skip a surface only after confirming it is absent):
   - **GitHub Actions** — every file in `.github/workflows/` (triggers, jobs, steps).
   - **Claude Code config & hooks** — `.claude/settings.json`, `.claude/settings.local.json`, and any `hooks` blocks within them; project skill rules under `.claude/rules/` and `rules/`.
   - **MCP servers** — any `mcpServers` block in `.mcp.json`, `.claude/settings*.json`, or other configured JSON.
   - **Composer scripts** — the `scripts` map in `composer.json` (`build`, `check`, `fix`, `analyse`, `skill-check`, `security-audit`, `test:coverage`, normalize/pint/rector/phpcs entries).
   - **Bundled CLI installer** — `bin/cursor-rules` and the install/sync logic it drives.
   - **Skills catalog** — `skills/*/SKILL.md` and their bundled `scripts/`, treated as automations themselves.
   - **Scheduler / cron** — any cron entry, Laravel scheduler definition, or `.claude/scheduled_tasks*` lock/registry.
2. **Classify live state per item.** Assign exactly one state, each backed by proof:
   - `LIVE` — confirmed running/passing: a recent successful workflow run, a command you executed returning success, or a hook observably firing.
   - `CONFIGURED-UNVERIFIED` — defined in config but no proof it ran or succeeded (no run history, no log, unconfirmed credential).
   - `BROKEN` — proof of active failure: failed run conclusion, non-zero command exit, missing referenced file/binary, malformed config.
   - `AUTH-OUTAGE` — defined and otherwise sound but blocked by a missing/expired token, secret, or MCP auth (cite the missing key, never its value).
   - `STALE` — references files, scripts, actions, or services that no longer exist, or pinned to a removed version.
   - `MISSING` — a capability the repo clearly needs (e.g. a quality gate exists in `composer check` but no workflow enforces it on PRs) with no automation covering it.
3. **Trace proof for each claim.** For GitHub Actions, read run conclusions via `gh run list --workflow <file>` / `gh run view`. For composer scripts, inspect the script body and any binary it calls; only mark `LIVE` if you ran it read-only or have a CI log proving it passed — never from the script name alone. For hooks/MCP, cite the exact config block and the auth/binary it depends on. Record the proof reference inline with the finding.
4. **Detect overlap and redundancy.** Cross-map surfaces that do the same work (e.g. a quality check duplicated between a workflow step and a composer script, two workflows triggering on the same event, two skills covering the same task). For each overlap, identify the canonical owner and the duplicate.
5. **Classify each finding by problem-type** before recommending: `active-breakage`, `auth-outage`, `redundancy`, or `missing-capability`. This drives the keep/merge/cut/fix decision.
6. **Recommend keep / merge / cut / fix** per item, justified only by the proof gathered. Never recommend deleting an item marked `CONFIGURED-UNVERIFIED` — recommend verifying it first.

## Output
A read-only report in the assignment language (per `@rules/reports/general.mdc`), in this structure:

### 1. Surface inventory
A table, one row per automation:

| # | Surface | Item | State | Problem-type | Proof |
|---|---------|------|-------|--------------|-------|
| 1 | GitHub Actions | `pr.yml` | LIVE | — | run #1234 conclusion `success` |
| 2 | Composer | `check` | CONFIGURED-UNVERIFIED | missing-capability | composer.json L#; no CI run found |

`State` is one of the six from step 2. `Proof` is a file path + line, a run id + conclusion, a log line, or the command output that established the state. Use `UNVERIFIED` and name the missing proof when none exists.

### 2. Findings
Per finding, numbered, with:
- **Surface + item** and its state.
- **Problem-type** (`active-breakage` / `auth-outage` / `redundancy` / `missing-capability`).
- **Evidence** — the concrete proof (no inference presented as fact).
- **Impact** — what fails, duplicates effort, or is silently unprotected.

### 3. Recommendations
Grouped under **Keep**, **Merge**, **Cut**, **Fix**. Each line names the item, the action, and the one-line justification traced to a finding number. Items in `CONFIGURED-UNVERIFIED` go under a **Verify first** note, not under Cut.

### 4. Next lane to strengthen
The single highest-leverage automation gap to close next (one `missing-capability` or `active-breakage`), with why it is the most valuable next step.

Close with an **Assumptions & gaps** footer listing every `UNVERIFIED` item and what command, secret, or access would confirm it.

## Done when
- Every existing automation surface in the repo has been enumerated and assigned exactly one live state.
- Every state claim cites concrete proof; nothing `LIVE` rests on a config reference alone, and every unproven item is marked `UNVERIFIED`.
- Each finding carries a problem-type and a keep/merge/cut/fix recommendation traced to its evidence.
- The report is read-only — no file, setting, workflow, or installer was modified.
- The next-lane recommendation names one concrete, justified action.

## Related skills
- `@skills/security-review/SKILL.md` — when an audited automation handles secrets, outbound requests, or shell execution, hand the exploitability question there.
- `@skills/smartest-project-addition/SKILL.md` — when the audit surfaces a `missing-capability`, that skill turns it into a single high-leverage proposal.
- `@skills/refresh-claude-md/SKILL.md` — when the inventory reveals the project's documented commands or stack drifted from reality, refresh `CLAUDE.md` there.
