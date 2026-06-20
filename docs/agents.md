# Agents

Agents are **Claude Code subagents** that act as a thin orchestration layer over the existing skills. They run in their own context window, delegate the real work to skills, and hand a clean result back to the caller.

```text
Rules  = long-lived project standards
Skills = reusable workflows
Agents = specialised orchestration roles over multiple skills
```

## Agent roster

Every agent has its own avatar under [`assets/agents/`](../assets/agents). When no custom artwork has been supplied yet, the slot falls back to the universal placeholder ([`placeholder.svg`](../assets/agents/placeholder.svg)) — swap `assets/agents/<name>.svg` to give an agent its own face.

### <img src="../assets/agents/argos.png" alt="argos avatar" width="48" align="left"> `argos` — code-review gatekeeper

The all-seeing code-review gatekeeper, named after **Argos Panoptes**, the hundred-eyed watcher nothing escaped. Give it a PR — from the current context or a tracker link (GitHub, JIRA, Bugsnag) — and it loads the source, runs the matching `code-review-*` wrapper skill, posts the findings to the PR, and hands back a `CR done` summary with links and Critical / Moderate / Minor counts.

- **Trigger:** a pull request needs reviewing.
- **Orchestrates:** `code-review-github`, `code-review-jira`, `code-review-bugsnag`.
- **Safety:** read-only — never edits, commits, pushes, or merges.

### <img src="../assets/agents/talos.png" alt="talos avatar" width="48" align="left"> `talos` — code-writing implementer

The tireless bronze automaton, named after **Talos**, the forged guardian that worked without rest. Give it a source — a tracker link (GitHub, JIRA, Bugsnag) or the current task — and it implements the fix or feature, validates it with tests, opens a pull request, and hands back an `Impl done` summary with links. It is the write-side counterpart to `argos`: `argos` is the tireless eye (review), `talos` the tireless hands (implementation).

- **Trigger:** an issue or task needs implementing.
- **Orchestrates:** `resolve-issue`.
- **Safety:** stops at the PR — never reviews its own work and never merges. If a caller explicitly instructs a merge, the only permitted path is `@skills/merge-github-pr/SKILL.md` — never `gh pr merge` or bare CLI.

### <img src="../assets/agents/metis.png" alt="metis avatar" width="48" align="left"> `metis` — problem-analysis advisor

The counsel of wise planning, named after **Metis**, the Titaness of deliberation and cunning planning (and mother of Athena). Give it a problem — a tracker link, a described failure, or an under-specified assignment — and it runs the analyze-problem framework, proposes the smallest safe solution, and publishes a reusable plan as a GitHub issue, then hands back an `Analysis done` summary. It is the thinking front-end to the roster: `metis` the mind (analysis), `talos` the hands (implementation), `argos` the eyes (review).

- **Trigger:** a problem needs analysis, or a vague assignment needs a proposed solution before any code is written.
- **Orchestrates:** `analyze-problem`.
- **Safety:** read-only — never edits, commits, pushes, or implements; publishes its plan to the tracker.

### <img src="../assets/agents/daidalos.png" alt="daidalos avatar" width="48" align="left"> `daidalos` — engineering-workflow orchestrator

The master craftsman who runs the workshop, named after **Daidalos**, the legendary engineer who designed the work and directed the makers. It is the **entry point** for a free-form engineering request — *"resolve a random issue"*, *"resolve this URL"*, *"implement this"* — and the conductor that drives the job to a clean, reviewed result. It resolves a concrete source, decides whether the task needs a plan first, then **delegates each step by dispatching the matching specialist agent** through the Task tool — `metis` (analysis, if needed), `talos` (implementation), `apollon` (fast scoped validation after each landing step), `argos` (the **review-and-fix loop `talos` ↔ `argos` to convergence**, no Critical/Moderate findings) — and reports the result to the user. `metis` the mind, `talos` the hands, `argos` the eyes, `apollon` the scoped-validation gate; `daidalos` the workshop lead that directs them.

- **Trigger:** a free-form engineering request — from a vague idea to a tracker link — that should be carried end to end.
- **Orchestrates (dispatches via the Task tool):** `metis` (analysis step — owns `analyze-problem`), `talos` (implementation step — owns `resolve-issue`, which runs a pre-PR self-check with `code-review` + `security-review` over its own diff — a self-validation pass, not the authoritative review that `argos` owns — to 0 Critical/Moderate before the PR), `apollon` (fast scoped validation gate — dispatched after talos PR-open and after argos convergence; runs only the tests covering the diff and verifies the relevant acceptance criteria; full `composer build` only for broad changes), `argos` (the `talos` ↔ `argos` convergence loop — owns `process-code-review` / `code-review-github`, `maxIterations = 5`); resolves the source itself reusing `autoresolve-oldest-github-issue` selection and `resolve-issue` source detection.
- **Convergence gate:** the run is done only at **0 Critical + 0 Moderate**; on `maxIterations` or a blocker it stops and escalates rather than reporting success. Merging stays a separate, explicit step — when instructed, always via `@skills/merge-github-pr/SKILL.md`, never ad-hoc CLI.
- **Safety:** read-only orchestrator — never analyses, implements, or reviews itself; it delegates each step by dispatching the matching specialist agent, the iteration loop is skill-driven (state lives in the skill the specialist owns), and it must be the top-level agent (not a nested subagent) per the one-level nesting rule below — that single level is what it spends to dispatch `metis` / `talos` / `apollon` / `argos`.

### <img src="../assets/agents/apollon.png" alt="apollon avatar" width="48" align="left"> `apollon` — test engineer

The test engineer who reveals the truth about a change, named after **Apollo**, the god of truth, prophecy, and order, and the unerring archer who never misses the mark. Give it a change — an issue, a PR, or the current task — and it authors the test coverage and validates the behaviour: it designs the test scenarios (edge cases, regression) from the assignment, writes the PHPUnit / Pest tests, generates the browser test scenarios, verifies every acceptance criterion, and hunts the broken flows — understanding **both the code and the product assignment**. It hands back a `Tests done` summary with the authored tests, the acceptance-criteria coverage, and the broken flows found.

- **Trigger:** a change needs test coverage authored and its behaviour validated — design tests, write PHPUnit/Pest tests, generate browser scenarios, verify acceptance criteria, find broken flows.
- **Orchestrates:** `create-test` / `create-missing-tests-in-pr` (PHPUnit/Pest authoring), `e2e-testing` (browser scenarios when Playwright is present), `test-like-human` (broken-flow hunting, publishes through `pr-summary`).
- **Safety:** write-capable for **test code only** — never touches application code, never merges, never pushes to a protected default branch. A code fix surfaced by a broken flow is handed to `talos`.
- **Two modes:**
  - **On-demand** — dispatched explicitly when full test authoring and validation is wanted (`create-test`, `e2e-testing`, `test-like-human`). `test-like-human` is never auto-chained from the review pipeline.
  - **Fast scoped validation gate (push-level)** — `daidalos` dispatches `apollon` automatically after each landing step: once after `talos` opens the PR and once after `argos` convergence. In this mode `apollon` derives the changed surface from the diff, runs only the affected tests, and verifies the relevant acceptance criteria against the diff. Full `composer build` is used only when the change is broad (shared/core/config files or more than 10 files changed). This gate runs at push-level granularity — inside the `argos` loop itself would violate the one-level nesting rule, so `daidalos` is the dispatcher, not `argos`. Handoff: `Tests done (scoped)` or `Blocked` (forwarded back to `talos`).

> A future top-level, cross-domain orchestrator (reserved name `zeus`) will sit above `daidalos` and coordinate non-engineering domains too (e.g. marketing). `daidalos` owns the engineering tier only.

## Naming convention — Greek mythology

Every agent is named after a figure from **Greek mythology**, chosen so the figure's role matches the agent's function. Use the lowercase name as the agent `name:` and file id (`agents/<name>.md`).

| Agent | Greek figure | Why it fits |
|---|---|---|
| `argos` | Argos Panoptes, the hundred-eyed all-seeing watcher | nothing escapes his gaze → thorough PR inspection |
| `talos` | Talos, the bronze automaton forged to work and guard without rest | tireless artificial labourer → forges working code |
| `metis` | Metis, Titaness of wise counsel and cunning planning | deliberation before action → problem analysis & planning |
| `daidalos` | Daidalos, the master craftsman who runs the workshop and directs the makers | head of production → routes engineering work to the right specialist |
| `apollon` | Apollo, god of truth, prophecy, and order, and the unerring archer | reveals the truth about a change and hits the acceptance mark → test authoring & validation |

Naming ideas for future agents: `themis` (order / verdict), `rhadamanthys` (fair judge), `athena` (wisdom / architecture), `hermes` (delivery / merge), `zeus` (top-level cross-domain orchestrator above `daidalos`).

## Anatomy of an agent

An agent is a Markdown file with frontmatter + a system prompt:

```markdown
---
name: argos
description: When to auto-delegate to this agent (the trigger sentence).
tools: Read, Glob, Grep, Bash
model: opus
---

System prompt: what the agent does, which skills it orchestrates, and the handoff it returns.
```

- **`name`** — lowercase, the id used as `subagent_type` / `@name`.
- **`description`** — drives auto-delegation; phrase it as the situation that should trigger the agent.
- **`tools`** — restrict to what the agent needs. A read-only reviewer needs `Read, Glob, Grep, Bash` only.
- **System prompt** — orchestration only. Delegate to skills via `@skills/<name>/SKILL.md`; **never duplicate a skill's rules** — defer to the skill as the source of truth.

## Handoff contract

An agent's final message is returned to the caller as the tool result, so it must be a self-contained handoff the next agent can act on without re-deriving context:

- **Status** — e.g. `CR done`.
- **Links** — the PR and the originating source (GitHub / JIRA / Bugsnag).
- **Result summary** — the numbers the caller needs (e.g. Critical / Moderate / Minor counts, a verdict).

**Language of the handoff / report.** Every agent writes the human-facing prose of its handoff and any end-user report in the **same natural language the assignment was given in** (if the request came in Czech, the handoff is in Czech). Identifiers stay verbatim regardless of that language — branch names, ticket / issue keys, links, severity labels, CLI commands, and skill / agent names are never translated, and two natural languages are never mixed inside a single handoff.

**How the language survives delegation.** When `daidalos` orchestrates, the assignment's natural language is not re-guessed at each hop — `daidalos` records it once in the shared brief's `## Language` field, writes every `Task` dispatch prompt in that language, and each specialist takes the brief's `## Language` field as the authoritative source for its reply. So a Czech request produces Czech output through the whole `metis → talos → apollon → argos` chain, not just in `daidalos`'s own final report.

## Shared task brief (inter-agent memory)

The handoff above is the *return* channel. For the *forward* channel — passing context **into** each agent efficiently — `daidalos` writes a **shared task brief** that every dispatched specialist reads, so the run's data is gathered once instead of re-derived by each agent.

- **Owner & gather phase.** Right after it resolves the source and **before the first dispatch**, `daidalos` runs a gather phase: it collects everything the task needs solved — the tracker payload and acceptance criteria (via the deterministic loaders), the relevant files / symbols / reproduction, known constraints, and its own **work-breakdown plan** (which specialist does what, with each one's success gate).
- **Location & lifecycle.** The brief lives at `.claude/run/<source-slug>.md`. `.claude/` is git-ignored, so it is **ephemeral and never committed**; `daidalos` removes it (`rm -f`) after the final report or a `Blocked` stop.
- **Read-then-append.** `daidalos` passes the brief's absolute path in every `Task` dispatch prompt. Each specialist **reads it first** as authoritative shared context, then **appends its own handoff section** (`### <agent> — <status>`) when it finishes, so the next specialist in the chain inherits the full history — source, plan, and every prior handoff — without `daidalos` re-passing it.
- **No new write scope.** Every agent already carries `Bash`, so the brief is created and appended through `Bash` redirection (`cat >> "$BRIEF" <<'EOF' … EOF`) to the git-ignored scratch path. No agent gains `Write` / `Edit` over the codebase from this — the read-only reviewers (`argos`, `metis`) and the read-only orchestrator (`daidalos`) keep their read-only-codebase stance; the brief is the only file they touch, and it is not source.
- **Top-level runs only.** The brief's value — a single gather shared across **separate** dispatched subagents — materialises only when `daidalos` runs **top-level** and dispatches `talos` / `argos` as real Task subagents (separate processes, shared filesystem). A `daidalos` invoked **as a subagent itself** has already spent the one nesting level, so it cannot dispatch separate specialists and instead returns a routing handoff (*Subagents of an agent*, case (b)) — there is no second process to read or append the brief, so the read-then-append loop does not apply to that nested case.

## Concurrency — working-tree write-lock

Several top-level `daidalos` runs can target the **same project at once** (interactively, or via `bin/cursor-rules-resolve-loop.sh`). When worktrees are not enabled they share **one git working tree**, so two runs that both write to it would corrupt each other's checkout and uncommitted edits. `daidalos` guards this with a **scope-conditioned write-lock**:

- **Read-only runs run in parallel.** An analysis-only run (dispatching `metis`) never modifies the working tree, so it takes **no** lock — any number of analysis runs overlap freely, with each other and with a writing run.
- **Writing runs serialise.** A full-delivery run (dispatching `talos`) acquires a lock before the dispatch and runs one at a time. A second writing run that finds a live holder stops with `Blocked` and a remediation (wait and retry, or request an isolated worktree) instead of dispatching `talos` into another run's changes.
- **Keyed to the toplevel.** The lock is a directory at `.claude/run/.daidalos-write.lock` inside the current toplevel's git-ignored `.claude/run/`. An isolated worktree is a different toplevel with its own lock, so worktree-isolated runs run in parallel even for full delivery; the shared tree (no worktree) contends on the same lock and serialises. Acquire is atomic (`mkdir`), a stale lock from a crashed run is reclaimed via a `kill -0` PID probe, and the lock is released on the final report and on any `Blocked` stop. See `agents/daidalos.md` *Concurrency & the working-tree write-lock* for the mechanism.

## Subagents of an agent

Claude Code subagents invoked via the Task tool generally **cannot spawn their own subagents** (one level of nesting). This shapes how the roster composes:

1. **A top-level orchestrator dispatches specialists through the Task tool.** `daidalos` runs as the top-level agent the user talks to, and spends its single nesting level dispatching `metis` / `talos` / `apollon` / `argos` directly. Each specialist then orchestrates its own skills inline — `talos` runs `resolve-issue`, `argos` runs `code-review-github`, and so on.
2. **Lens skills called inline** by an orchestrating skill — e.g. `code-review-github` already runs `code-review`, `security-review`, `api-review`, `assignment-compliance-check` inline. This is what each dispatched specialist does in its own context, and it is also the fallback when no further nesting level is available.
3. **Parallel fan-out via the Workflow tool** — a DAG of agents for heavy runs that genuinely need concurrency.

Because of the one-level limit, an orchestrator like `daidalos` must be the **top-level agent the user talks to** — it delegates each step by dispatching the matching specialist agent (or, if `daidalos` was itself invoked headless and the nesting level is already spent, returns a routing handoff for the caller to execute), never by becoming a nested subagent that tries to spawn `metis` / `talos` / `apollon` / `argos` from inside another agent. A future `zeus → daidalos → specialist` chain cannot stack three Task-subagent levels; it must collapse to a single dispatch level plus the inline / Workflow model.

### End-to-end run (agent-dispatched, skill-owned loop)

The `daidalos` run carries a request all the way to a clean, reviewed result. `daidalos` resolves the source itself, then **dispatches each step as the matching specialist agent through the Task tool**; the iterative `talos` ↔ `argos` review-and-fix loop is **owned by the skill the dispatched specialist drives** (its state lives there), not modelled as agents calling agents:

```text
user → daidalos                                         (top-level; resolves source, then dispatches via Task tool)
         │  resolve source (autoresolve-oldest-github-issue selection / resolve-issue source-detection)
         │  analyse? ── yes ─→ Task ▶ metis   (= analyze-problem → plan)
         │     │ no
         ▼     ▼
       Task ▶ talos   (= resolve-issue)
         │        └─ pre-PR self-check: code-review + security-review (self-validation, not the authoritative review) → 0 Critical/Moderate → opens PR
         ▼
       Task ▶ apollon   (fast scoped validation — diff-targeted tests + acceptance-criteria check; full build only for broad changes)
         │        └─ Tests done (scoped) → proceed | Blocked → escalate to talos
         ▼
       Task ▶ argos   (= process-code-review / code-review-github — the talos ↔ argos loop)
         │        └─ convergence loop: code-review-github (quiet) + fixes, maxIterations 5 → 0 Critical/Moderate
         ▼
       Task ▶ apollon   (fast scoped validation — final gate after convergence)
         │        └─ Tests done (scoped) → proceed | Blocked → escalate to user
         ▼
       daidalos → reports result to the user   (merge stays a separate, explicit step — always via @skills/merge-github-pr/SKILL.md)
```

The apollon dispatch runs at **push-level granularity** — once after `talos` opens the PR and once after `argos` converges. Running it inside the `argos` loop would require `argos` to dispatch `apollon` as a subagent, which violates the one-level nesting rule (the nesting level is already spent on dispatching `argos` from `daidalos`). `daidalos` is therefore the correct dispatcher for both `apollon` passes.

The convergence gate is **0 Critical + 0 Moderate**; on `maxIterations` or a blocker the run stops and escalates instead of reporting success.

## Troubleshooting — subagent file writes blocked

**Symptom:** a write-capable agent (`talos`) reports it cannot write files — *"sandbox blocking file writes"* — and the run stops with a `Blocked: sandbox denied file write` handoff (or the main thread is tempted to finish the implementation itself).

**Cause:** the agent declares `Write` / `Edit` in its frontmatter, but those tools are *capabilities*, not grants. A dispatched subagent runs **non-interactively** — when its `Edit` / `Write` is not already pre-allowed for the path it targets, it cannot fall back to an interactive approval the way the main thread can, so the write is denied at runtime. This is an environment setting, not something the agent definition or this package can grant.

**Correct behaviour (already enforced):** the blocked agent returns `Blocked: sandbox denied file write` and the orchestrator escalates it — the work is **never** silently completed outside the delegated, reviewed pipeline (`@rules/compound-engineering/general.mdc` *Blocked delegation is a hard stop*).

**Remediation (the human enables subagent writes) — pre-allow scoped `Edit` / `Write` on the working tree.** Add two scoped allow entries to **`permissions.allow`** in the project's `.claude/settings.local.json`, naming the project's absolute path:

```json
{
  "permissions": {
    "allow": [
      "Edit(//Users/me/Projects/my-app/**)",
      "Write(//Users/me/Projects/my-app/**)"
    ]
  }
}
```

This is the permanent, recommended fix: a dispatched subagent then writes the working tree without an interactive prompt. `settings.local.json` (personal, git-ignored) is the right home because the entries carry your machine-absolute path. A blanket `acceptEdits` permission mode also works for an interactive session, but the scoped allow entries survive across sessions and headless runs. See the Claude Code [permissions](https://code.claude.com/docs/en/permissions) and [subagents](https://code.claude.com/docs/en/sub-agents) docs.

**Installer shortcut (opt-in).** The fix above can be applied for you: run the installer with `--allow-subagent-writes` (with `--editor=claude` or `--editor=all`) and it prepends `Edit(//<project>/**)` and `Write(//<project>/**)` to `permissions.allow` in the project's `.claude/settings.local.json`, validating the result so it can never be written malformed. It leaves existing allow entries untouched and is idempotent. This package still grants **nothing by default** — the flag is the explicit, human-owned opt-in, never automatic.

## Distribution

The installer copies `agents/` to `.claude/agents/` for `--editor=claude` and `--editor=all` only — Claude Code is the only editor with a native subagent format, so `--editor=cursor` and `--editor=codex` skip agents.

## Adding a new agent

1. Pick a Greek figure whose myth matches the job; use the lowercase name.
2. Create `agents/<name>.md` with the frontmatter + an orchestration-only system prompt that delegates to skills and returns a handoff.
3. Add it to the README *Claude Code Subagents* table.
4. Add a test asserting the file ships with its required frontmatter (mirror the `argos` test in `tests/InstallerTest.php`).
5. Run `composer build` — the installer file-count tests pick up the new agent automatically.
