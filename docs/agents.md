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
- **Safety:** stops at the PR — never reviews its own work and never merges.

## Naming convention — Greek mythology

Every agent is named after a figure from **Greek mythology**, chosen so the figure's role matches the agent's function. Use the lowercase name as the agent `name:` and file id (`agents/<name>.md`).

| Agent | Greek figure | Why it fits |
|---|---|---|
| `argos` | Argos Panoptes, the hundred-eyed all-seeing watcher | nothing escapes his gaze → thorough PR inspection |
| `talos` | Talos, the bronze automaton forged to work and guard without rest | tireless artificial labourer → forges working code |

Naming ideas for future agents: `themis` (order / verdict), `rhadamanthys` (fair judge), `athena` (wisdom / architecture), `hermes` (delivery / merge).

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

## Subagents of an agent

Claude Code subagents invoked via the Task tool generally **cannot spawn their own subagents** (one level of nesting). Model an agent's "subagents" two ways:

1. **Lens skills called inline** by an orchestrating skill — e.g. `code-review-github` already runs `code-review`, `security-review`, `api-review`, `assignment-compliance-check` inline. This is the default and works today.
2. **Parallel fan-out via the Workflow tool** — a DAG of agents for heavy runs that genuinely need concurrency.

## Distribution

The installer copies `agents/` to `.claude/agents/` for `--editor=claude` and `--editor=all` only — Claude Code is the only editor with a native subagent format, so `--editor=cursor` and `--editor=codex` skip agents.

## Adding a new agent

1. Pick a Greek figure whose myth matches the job; use the lowercase name.
2. Create `agents/<name>.md` with the frontmatter + an orchestration-only system prompt that delegates to skills and returns a handoff.
3. Add it to the README *Claude Code Subagents* table.
4. Add a test asserting the file ships with its required frontmatter (mirror the `argos` test in `tests/InstallerTest.php`).
5. Run `composer build` — the installer file-count tests pick up the new agent automatically.
