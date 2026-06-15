---
name: autonomous-loops
description: "Use when choosing how to run Claude Code autonomously on this project — from a single sequential pipeline to multi-agent DAG orchestration. A reference catalog of loop patterns anchored to this repo's real tooling (resolve-issue, autoresolve-oldest-github-issue, code-review-github, process-code-review, merge-github-pr, /loop), with composer build / composer skill-check as the quality gate between iterations."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

This is a reference skill. It does not run a loop itself — it helps you pick the right autonomous-loop architecture for a task and wire it to this project's existing skills, gates, and CLI tooling. Patterns are ordered simplest → most complex. Use the lightest one that fits.

## Constraints
- Apply `@rules/git/general.mdc` — never push to `main`, one logical change per commit, English commit messages, worktree/branch per work unit.
- Apply `@rules/code-review/general.mdc` to every review stage in a loop — reviewers are read-only and must not be the same context that wrote the code.
- `composer build` and `composer skill-check` are non-negotiable gates between iterations. An iteration that does not pass them is not "done" and must not advance the loop or merge.
- Every loop needs an explicit stop condition (max iterations, no open work, or a documented blocker). Never run unbounded.
- Stop on blockers, never force through them: merge conflict, failing CI, unresolved Critical/Moderate findings, or a gate failure ends the loop with a report.
- Each work unit runs in its own git worktree so parallel units cannot corrupt each other's tree.
- Do not expose sensitive/internal details in user-facing loop reports.

## Use when
- Deciding whether a task should run autonomously at all, and with which architecture.
- Setting up a scripted or scheduled pipeline that chains this project's skills without a human between steps.
- Running multiple independent work units in parallel and coordinating their merges.
- You need context to survive across otherwise-independent iterations.

## When NOT to run autonomously
- The requirements are vague or the acceptance criteria are missing — autonomy multiplies hallucination. Resolve scope with a human first (see `prepare-issue-context`).
- The change touches auth, payments, migrations on production data, or anything irreversible.
- One focused edit a human could do faster than wiring a loop.
- No reliable automated gate exists for the work (nothing for `composer build` / tests to verify).

## Patterns

### 1. Sequential pipeline
Lowest complexity. Break the work into ordered, non-interactive steps; each step runs in a fresh context and builds on the filesystem state of the previous one. Order matters and steps exit on first failure.

Use when the task is a single focused change with a known sequence (implement → clean up → verify → commit).

```bash
set -e
# branch per work unit per @rules/git/general.mdc — never on main
git switch -c feat/<scope>-<slug>
claude -p "Implement <scope> per <spec>. TDD: failing test first."
claude -p "Review the diff. Remove redundant type/framework tests and over-defensive checks. Keep business-logic tests."
composer build && composer skill-check   # gate: must pass before commit
claude -p "Create one conventional commit for the staged changes per @rules/git/general.mdc."
```

Tip: prefer a separate cleanup step over negative instructions ("don't over-test") inside the implement step — two focused agents beat one constrained one.

### 2. Built-in `/loop`
Low complexity, no script. Use the built-in `/loop` to re-run a prompt or slash command on an interval (or self-paced). Good for polling and short repeating chores where each pass is independent.

Use when you want a recurring task without authoring a runner: poll PR status, re-run a check, keep an eye on a long job.

```text
/loop 5m run `composer skill-check` and report only new failures
/loop autoresolve-oldest-github-issue   # self-paced, one issue per pass
```

Still bounded in practice: stop it when there is no open work or a blocker appears.

### 3. Single-issue end-to-end chain
Medium complexity. This is the project's existing autonomous unit: pick one issue and drive it resolve → review → process-feedback → merge, stopping at any blocker. You rarely need to build this — it already exists.

Use when one tracker issue should be taken from open to merged without a human between steps.

Entry point: `@skills/autoresolve-oldest-github-issue/SKILL.md`, which chains:
1. `@skills/resolve-issue/SKILL.md` — branch, implement, local code-review + security-review loop, pre-push gates, PR.
2. `@skills/code-review-github/SKILL.md` — review the PR, post findings.
3. `@skills/process-code-review/SKILL.md` — drive findings to Critical+Moderate == 0.
4. `@skills/merge-github-pr/SKILL.md` — merge only if mergeable, CI green, approved.

The chain processes exactly one issue and stops on the first blocker — never wrap it in a second loop that "retries" past a blocker.

### 4. Continuous multi-issue loop
Medium complexity. Run the single-issue chain repeatedly across a backlog, with a shared notes file bridging context between otherwise-independent passes and a hard stop limit.

Use when a labelled backlog (default `Resolve_by_AI`) should be worked down over a session.

```bash
set -e
MAX_RUNS=5; NOTES=.loop/SHARED_NOTES.md   # context bridge across iterations
for i in $(seq 1 "$MAX_RUNS"); do
  gh issue list --label Resolve_by_AI --state open --limit 1 | grep -q . || break  # stop: no work
  claude -p "Read $NOTES for prior-pass context. Run @skills/autoresolve-oldest-github-issue/SKILL.md \
             for label Resolve_by_AI. Append outcome, decisions, and follow-ups to $NOTES. \
             Stop and report if the chain hit any blocker."
done
```

Stop conditions: `MAX_RUNS` reached, no eligible issue, or a blocker surfaced by the chain. The notes file is what lets pass N learn from pass N-1 despite the fresh context — keep it short and factual.

### 5. Parallel units with merge coordination
Highest complexity. Decompose a large spec into independent work units, give each its own git worktree, run each through a complexity-tiered pipeline, and land them through a merge queue that rebases and re-runs gates per unit.

Use when one feature is large enough to split into several units that can progress at once, and only then.

Per work unit: `{ id, deps, acceptance, tier }`. Pipeline depth scales with `tier`:
- **trivial** → implement → `composer build`/`skill-check`.
- **small** → implement → gates → `code-review`.
- **medium** → analyze (`analyze-problem`) → implement → gates → `code-review` + assignment check → fix.
- **large** → all of the above → security-review → final review.

Model routing: run implementation on a lighter model, run the review/decomposition stages on a heavier model — the reviewer must be a separate context from the author per `@rules/code-review/general.mdc`.

```text
for each layer of the dependency DAG (deps satisfied):
  for each unit in layer (parallel):
    worktree = git worktree add ../wt-<unit.id> -b feat/<unit.id>
    run tier pipeline in worktree
  merge queue (sequential when worktrees overlap files):
    rebase unit branch onto main → composer build && composer skill-check → merge
    on conflict / gate failure → evict unit with full context, re-enter next pass
```

Non-overlapping units can land speculatively in parallel; overlapping units land sequentially with rebase. An evicted unit re-enters the next pass carrying its conflict/failure context — never force its merge.

## Quality gates
- Between every iteration and before any merge: `composer build` then `composer skill-check`. Both must pass; a failure ends the iteration, not advances it.
- Review stages stay read-only (`@rules/code-review/general.mdc`); the reviewing context must differ from the implementing context.
- The single-issue chain's own gates (`resolve-issue` local review loop, `code-review-github`, `process-code-review` convergence, `merge-github-pr` pre-checks) are authoritative — a loop wrapping them must never bypass or retry past them.
- Tier the gates to complexity: trivial units need only build+tests; large units add security and final review. Do not under-gate risky units.

## Done when
- The right pattern was chosen for the task's complexity, and a lighter pattern was not adequate.
- The loop has an explicit, reached stop condition (max iterations, empty backlog, or a documented blocker).
- Every iteration that advanced or merged passed `composer build` and `composer skill-check`.
- Each work unit ran in its own branch/worktree and was committed per `@rules/git/general.mdc`.
- Any blocker was reported and left for a human, never forced through.
- A final report lists per-unit outcomes (merged / stopped + reason) and the PR URLs.
