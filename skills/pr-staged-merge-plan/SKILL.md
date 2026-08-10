---
name: pr-staged-merge-plan
description: "Use when a large pull request should be merged and deployed in safe parts instead of all at once. Analyzes every commit in the PR, groups them into atomic logical units that each ship independently without downtime, orders the units by dependency and rollback risk, proposes squashes for a clean human-readable history, and proposes corrected subjects for vague or inaccurate commit messages. Read-only — it proposes the plan and never rewrites history, pushes, or merges."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/git/general.mdc` — every proposed commit subject follows *Commit Messages* (English, `type(scope): short description`, lowercase, allowed types only, no trailing period) and every proposed unit follows *One phase = one commit*.
- **Read-only.** Never run `git rebase`, `git commit --amend`, `git cherry-pick`, `git reset`, `git push`, `gh pr merge`, or `gh pr edit`. This skill produces a plan; a human or a follow-up skill executes it.
- Load PR context only through `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>`. Never call `gh pr view` / `gh api /repos/.../pulls/...` directly; fall back to the GitHub MCP server only when the loader is unavailable (exit code 2/3). Local `git log` / `git diff` reads against the loaded base and head refs are expected and allowed.
- Merging a unit is owned by `@skills/merge-github-pr/SKILL.md`; interactive-rebase, conflict, and force-with-lease mechanics are owned by `@skills/git-workflow/SKILL.md`. Reference them — do not restate them.
- **Commit-level splitting inside one PR is owned by the CR gate, not by this skill.** The **Commit Split & Atomic Deployability Gate** (`@rules/code-review/general.mdc` *Commit Split & Atomic Deployability Proposal — canonical walk-through*) runs on every code review and raises a **Critical** finding with a commit-level repartition of the PR's own history. This skill is the on-demand deeper plan: it splits the work into **several pull requests**, each with its own review-and-merge gate. Raise one plan per PR, never both — when a CR proposal shows units that cannot ship inside one PR (destructive migration, consumer-contract change needing expand → migrate → contract across deploys), the CR finding points here and this skill owns the answer.
- **Every unit is its own pull request and carries its own hard code-review gate** (`@rules/git/general.mdc` *Merging*): the plan never proposes merging a unit whose own diff has not been reviewed to 0 Critical + 0 Moderate.
- **No work may be dropped or re-scoped.** The union of the proposed units must equal the current PR's diff exactly — same files, same net content. State this reconciliation explicitly in the output.
- Do not publish the plan anywhere unless the user asks for it. When asked, apply `@rules/reports/general.mdc` (assignment language) and post it via `skills/code-review-github/scripts/upsert-comment.sh <NUMBER|URL> -` — never a raw `gh pr comment`.

## Use when
- A pull request is large enough that merging it in one go risks an outage or an unreviewable diff.
- The change must reach production incrementally (schema migration + backfill + reader switch, contract change with external consumers, risky refactor behind a flag).
- The PR history is noisy — fixup commits, `wip`, formatting-only commits — and should be condensed into human-readable logical commits before merge.
- Commit subjects are generic or contradict their own diff and need corrected names.

## Do not use when
- The PR is already merged or closed — there is nothing left to stage.
- The PR is a single atomic change (one concern, one or two commits) that cannot be split without breaking it. Say so and stop; do not invent artificial units.

## Execution

### 1. Load the PR and its commits
- Run `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>`. Read `baseRefName`, `headRefName`, `state`, `isDraft`, `commits[]`, `files[]`, and `closingIssues[]` off the resulting JSON.
- Refuse to continue when `state != "OPEN"` (nothing to stage) and report why.
- Read the full messages and per-commit diffs locally: `git log --reverse --format='%H%n%s%n%b' <base>..<head>` and `git show --stat <sha>` per commit. Read the actual diff of every commit — a subject is a claim, not evidence.

### 2. Load the assignment
- Load every entry in `closingIssues[]` through the same loader (or the JIRA / Bugsnag loader when the PR links one) and extract the stated requirements and acceptance criteria.
- Map each requirement to the commits that implement it. A requirement split across commits is the strongest signal for where a unit boundary belongs; a commit that maps to no requirement is either a pre-existing fix, noise, or scope creep — label it as such.
- **No linked issue.** When `closingIssues[]` is empty and the PR references no JIRA / Bugsnag source, state `no linked issue — grouping by concern` and derive the units from the commits' own concerns instead of from requirements. In that mode the Output table drops the *requirement it satisfies* column and step 8's missing-requirement blocker does not apply — never invent a requirement to fill the column. Everything else (steps 3–7, the reconciliation) runs unchanged.

### 3. Classify every commit
For each commit record: the concern it serves, the layer it touches (schema, backend, frontend, config, tests, tooling), whether it is self-contained, and its class:
- **functional** — carries part of the assignment;
- **repair** — only fixes an earlier commit of the same PR (`fix tests`, typo, review feedback, `wip`);
- **noise** — formatting / fixer output / merge commit with no behavioral content;
- **pre-existing** — an unrelated fix that came along (per `@skills/resolve-issue/SKILL.md` *Pre-existing issue handling*).

### 4. Group commits into units
A **unit** is one coherent outcome that can be reviewed, merged, and deployed on its own. Grouping rules:
- One unit maps to one requirement or one infrastructural step — not to one file and not to one author's working session. With no linked issue (step 2), map one unit to one commit concern instead.
- **repair** commits are squashed into the commit they repair and never form a unit; **noise** commits fold into the nearest functional commit of the same unit.
- **pre-existing** fixes become their own unit, ordered first — they are independent of the assignment and the cheapest thing to ship.
- Two commits that cannot be deployed apart stay in one unit. Never split for cosmetics.
- Prefer 2–5 units. When the plan needs more than roughly seven, say plainly that the PR should have been several issues, and still deliver the ordered plan.

### 5. Verify each unit is independently shippable
A unit may ship alone only when **every** check below passes. Walk them per unit and record the verdict; a unit that fails a check must be merged with its dependency, reordered, or gated behind a flag. **Derive each verdict by reading the code** — never materialize a unit's branch to test it. Actually building each stacked unit belongs to the human executing the plan (the *Execution sketch* says so per unit); doing it here would breach the read-only constraint. When reading cannot settle a check, record the verdict as `unverified — <what to run>` instead of asserting it.
1. **Green on its own** — `base + units 1..N` must pass the project's gate (`composer build` in this repository) at every N, not only at the end. Assess it from the unit's imports, call sites, and test references; the human confirms it by running the gate on each unit's branch.
2. **No forward reference** — nothing in the unit calls a symbol, config key, route, migration, translation key, or asset that only a later unit introduces.
3. **Expand before contract** — additive schema / contract change first, backfill next, switch readers, remove the old shape last. A destructive migration never shares a unit with the code that stops using the dropped column.
4. **Prerequisites first, inert by default** — config, env, and feature-flag definitions ship before the code that reads them, with a default that keeps the new path off until the flag flips.
5. **Tests travel with their behavior** — no unit whose tests reference production code that lands later, and no unit that ships behavior with its coverage deferred.
6. **Reversible alone** — deploying and rolling back the unit returns the system to its pre-unit state. A unit that is not reversible (destructive migration, irreversible data mutation, outbound side effect) is flagged, ordered as late as its dependencies allow, and given an explicit rollback note.
7. **Consumer contracts stay compatible for the whole rollout** — an API response, queue payload, or event shape change splits into add-new → migrate consumers → remove-old, so no deployed consumer ever sees a shape it cannot read.
8. **In-flight work survives** — a job / listener rename or payload change tolerates messages enqueued by the previous unit for as long as the queue can hold them.

### 6. Order the units
- Order by dependency first (topological — a unit never precedes what it needs), then by ascending deployment risk, then by reversibility (reversible units early, irreversible last).
- State the base of each unit: independent units branch off the default branch; dependent units stack on their predecessor's branch, and their PR base is that predecessor.
- Name every dependency edge explicitly so a reader can see why the order cannot be shuffled.

### 7. Propose commit subjects
Flag a subject when it is:
- **generic** — `fix`, `update`, `changes`, `wip`, `misc`, `improvements`, `refactor code`, or any subject that would fit any diff;
- **malformed** — missing or wrong `type`, missing or wrong `scope`, wrong language, trailing period, uppercase type/scope;
- **inaccurate** — contradicted by its own diff: claims a fix while adding a feature, names a scope or file it does not touch, or describes only a fraction of what it changes.

Derive the replacement from the diff, not from the old subject, and keep it within `@rules/git/general.mdc` *Commit Messages*. Present every proposal as `old → new` with the one-line reason. Leave a subject that is already accurate untouched and say so — an unnecessary rename destroys the reviewer's memory of the commit.

### 8. Surface blockers
Report — and do not paper over — any of:
- a commit that touches the same lines as a commit proposed for a *later* unit (the split will conflict on cherry-pick; name the file);
- a unit that cannot satisfy step 5 under any ordering, with the reason;
- a destructive migration or irreversible data change anywhere in the PR;
- a requirement from step 2 that no commit implements, or a commit that implements nothing the assignment asked for (skip this bullet entirely in the *no linked issue* mode — there is no requirement set to compare against).

## Output

Return one markdown document, no local files:

1. **Verdict** — `splittable into N units` / `single atomic change — ship as one PR` / `blocked — <reason>`.
2. **Staged merge plan** — a table of units in merge order: `#`, unit title, branch name (English, per `@rules/git/general.mdc`), PR base, commits it contains, requirement it satisfies (omit this column in the *no linked issue* mode), reversible (yes/no).
3. **Per unit** — the commits it carries (final subjects), the step-5 verdict, dependency edges, what to verify after deploying it, and the rollback note for an irreversible unit.
4. **Commit history proposal** — the squash groups (which commits collapse into which) and the `old → new` subject table with reasons; explicitly list the subjects kept as-is.
5. **Reconciliation** — confirmation that the units' union equals the PR diff, plus the file-level check that proves it.
6. **Blockers & notes** — everything from step 8, or `none`.
7. **Execution sketch** — the ordered outline a human follows (branch per unit, which commits to cherry-pick or which `git rebase -i` steps to take, and the review-then-merge gate per unit), delegating mechanics to `@skills/git-workflow/SKILL.md` and the merge itself to `@skills/merge-github-pr/SKILL.md`.

## Principles
- Deployability beats tidiness — a beautiful split that breaks production is a failed split.
- Every unit is a complete thought: it builds, it is covered, it is reviewable, and it can be rolled back.
- Never lose work in the rewrite; reconcile the diff before proposing the plan.
- Rename only what misleads. Accurate history is the goal, not uniform history.
- Propose, do not perform — history rewriting and merging stay a human decision.

## Done when
- Every commit in the PR is assigned to exactly one unit (or to a squash group inside one).
- Every unit has a step-5 verdict and an explicit place in the dependency order.
- Every misleading commit subject has a proposed replacement that satisfies `@rules/git/general.mdc`; accurate ones are listed as kept.
- The reconciliation shows the units' union equals the PR diff.
- Blockers are reported rather than resolved silently, and no git history was modified.
