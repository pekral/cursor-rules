---
name: blueprint
description: "Use when a single objective is too large for one pull request and must span multiple sessions or PRs. Turns the objective into a sequenced construction plan of 3-12 one-PR steps, each with a cold-start context brief, dependency edges, and exit criteria, then reviews it adversarially and registers it as Markdown."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

# Blueprint

## Constraints
- Apply `@rules/git/general.mdc` — branch, commit, and PR conventions below come from it.
- Apply `@rules/compound-engineering/general.mdc` — the plan is durable memory the next agent reuses, not throwaway prose.
- Apply `@rules/laravel/architecture.mdc` when the project uses `pekral/arch-app-services`, so each step lands in the correct layer.
- Plan only. Do not implement, commit, or push any step.
- Output Markdown only. English only.
- Every step must be one pull request: independently reviewable and mergeable on its own.
- Break the objective into 3-12 steps. Fewer means it likely fits one PR; more means the objective is too broad and should be split first.
- Each step must be executable cold by `@skills/resolve-issue/SKILL.md` or a fresh agent with no prior context.
- Do not invent dependencies on tooling that may be absent. Degrade gracefully when GitHub CLI (`gh`) is missing.

## Use when
- An objective clearly exceeds one pull request (a migration, a multi-layer feature, a cross-cutting refactor).
- Work will span multiple sessions or multiple agents and needs a sequenced, resumable plan.
- You need to know which parts can run in parallel and which must be sequential before starting.

Do not use for single-PR tasks (use `@skills/analyze-problem/SKILL.md` or `@skills/resolve-issue/SKILL.md`), for splitting an objective into tracker issues without a dependency graph (use `@skills/create-issues-from-text/SKILL.md`), or when the user wants one high-leverage suggestion (`@skills/smartest-project-addition/SKILL.md` / `@skills/product-capability/SKILL.md`).

## Execution

Run the five phases in order. Do not register a plan that has not passed Review.

1. **Research** — Establish ground truth before planning.
    - Read the actual code, layers, and conventions the objective will touch. Per `@rules/compound-engineering/general.mdc`, find the existing part of the system the work extends before inventing a new abstraction.
    - Walk `git log` / `git blame` over the affected area to learn how it evolved and what was already tried or reverted.
    - Detect the git baseline: confirm the repo, the default branch (`master`), and whether `gh` is available (`gh auth status`). Record the result; it drives the PR template in Output.
    - For an unfamiliar pattern, library, or security-sensitive surface, consult current authoritative references and cite them.

2. **Design** — Decompose the objective into 3-12 one-PR steps.
    - Each step is the smallest change that delivers reviewable value and can merge alone.
    - Sequence steps so each builds on a merged predecessor; never assume an unmerged step's code.
    - Map dependency edges: for every step, list which steps must merge before it. Steps with no shared files and no dependency edge between them may run in parallel.
    - Name the layer each step lands in (Action, Service, Repository, etc.) when `@rules/laravel/architecture.mdc` applies.

3. **Draft** — Write the per-step plan (structure in Output).
    - Give every step a self-contained context brief: enough background, file paths, and conventions for a fresh agent to execute it without reading the other steps.
    - Write concrete, observable exit criteria per step (tests, behavior, verification commands).
    - Add the parallelism summary: the dependency graph and which steps may run concurrently.

4. **Review** — Adversarially check the draft against the anti-patterns below before registering. Fix every hit; re-run until clean.
    - A "step" that spans multiple PRs, or is too small to justify its own PR.
    - A step whose context brief is not self-contained (assumes prior conversation or another step's unmerged work).
    - Missing, unobservable, or untestable exit criteria.
    - Hidden ordering: two steps marked parallel that touch the same files or share an unstated dependency.
    - A new abstraction where an existing part of the system already fit (`@rules/compound-engineering/general.mdc`).
    - Branch / commit / PR wording that violates `@rules/git/general.mdc` (wrong type, base branch, period, attribution).
    - A plan that assumes `gh` exists when Research found it absent.

5. **Register** — Persist the approved plan as Markdown in the repo (e.g. `docs/plans/<objective-slug>/`): one `README.md` index plus one file per step, or a single plan file when small. State the path so the next agent picks it up.

## Output

Register a plan index plus one entry per step.

**Plan index** (`README.md`): objective in one or two sentences; ordered step list; the parallelism summary; git baseline (default branch `master`, `gh` present yes/no).

**Per step:**
- **Title** — `type(scope): short description`, lowercase type/scope, no trailing period (`@rules/git/general.mdc`).
- **Branch** — feature branch off `master` (e.g. `feat/<short-slug>`).
- **Context brief** — self-contained background, file paths, and conventions; readable cold, with no reference to other steps' unmerged work.
- **Tasks** — concrete, ordered actions implementing this one PR.
- **Dependencies** — the step IDs that must merge first; `none` if it can start immediately.
- **Exit criteria** — observable, verifiable conditions that prove the step is done: tests to pass, behavior to confirm, and the exact verification commands to run.
- **PR** — title in English; body as Markdown linking the issue (`Closes #N`) when one exists. Open with `gh` when available; otherwise output the title and body for the human to open manually and say so.

**Parallelism summary** — the dependency graph (e.g. `1 -> 2 -> 4`, `3` parallel to `2`) and an explicit list of step sets that may run concurrently because they share no files and no dependency edge.

## Done when
- The plan has 3-12 steps, each one mergeable pull request.
- Every step has a self-contained cold-start context brief, explicit dependencies, and observable exit criteria.
- The parallelism summary states what can run concurrently and what must be sequential, with no hidden shared-file conflicts.
- The Review pass found no remaining anti-patterns.
- Branch, commit, and PR templates follow `@rules/git/general.mdc` and degrade gracefully when `gh` is absent.
- The plan is registered as Markdown in the repo and its path is reported.
