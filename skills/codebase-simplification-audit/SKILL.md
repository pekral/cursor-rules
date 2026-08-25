---
name: codebase-simplification-audit
description: "Use when the user asks for an audit of the codebase or decides to refactor a part of it, and wants the cleanest achievable answer rather than a quick opinion. Runs a coordinator-driven, read-only sweep for materially useful simplifications in data structures, state representation, control flow, algorithms, and ownership — every subsystem inventoried, each finding independently verified, the audit itself audited. Proposes only; never edits, tests, commits, or pushes."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints

- **Audit-only, read-only, no exceptions.** Do not edit files, write or run tests, implement a recommendation, commit, or push. Read-only inspection commands (`git log`, `git grep`, `rg`, `find`, `cat`, `sed -n`) are the whole toolset. The deliverable is a report; the repository is unchanged when the audit ends.
- Apply `@rules/refactoring/general.mdc` — the shared definition of refactoring and the ban on big-bang rewrites. Every recommendation must be reachable by incremental steps that keep the codebase green between them.
- Apply `@rules/compound-engineering/general.mdc` *Project-local agent instructions are part of the rule set* — load the project's own `CLAUDE.md` and the sibling instruction files that section lists before the first subsystem review. They frequently declare the intentional semantics that turn a "simplification" into a misreading.
- Apply `@rules/compound-engineering/general.mdc` *Temporary-file hygiene* — the working scratchpad is scratch, not an artifact. Write it under the session scratchpad directory and delete it when the audit ends; the report returned to the caller is the deliverable.
- Apply `@rules/php/core-standards.mdc` **only when the audited project is a PHP project** (a PHP stack in `composer.json`) — its *Design Principles* own the YAGNI and speculative-interface bars this audit leans on. Skip it for a non-PHP codebase.
- If the audited project uses Laravel, also apply `@rules/laravel/architecture.mdc` — its layer boundaries are the project's declared ownership model, so an ownership finding must be expressed in its vocabulary rather than a freshly invented one.
- Output in English, Markdown only.

### Boundary against neighbouring skills

Raise a concern once, in the skill that owns it — never file the same item twice:

- `@skills/class-refactoring/SKILL.md` — one class, and in `MODE=apply` it *changes* code. This skill is repo-wide and never changes anything. When an accepted recommendation is ready to implement, hand the target class to that skill.
- `@skills/automation-audit-ops/SKILL.md` — audits the repo's automation (workflows, hooks, scripts), not its data model.
- `@skills/production-audit/SKILL.md` — answers ship / block for a release; this skill answers what the code should look like.
- `@skills/blueprint/SKILL.md` — turns **one** objective into a sequenced multi-PR plan. This audit's ranked output is a natural input to it; do not duplicate its planning format here.
- `@skills/analyze-problem/SKILL.md` — starts from a reported problem and finds its root cause. This audit starts from no complaint at all and looks for representations that invite future problems.

---

## Use when

- The user asks to audit, review, or assess the codebase as a whole, or a named part of it.
- The user has decided to refactor an area and wants the cleanest target representation identified before any code moves.
- A subsystem has accumulated enough state flags, shape assumptions, or branching that the team suspects the model itself is wrong.

Do **not** run it for a routine code review of a diff (`@skills/code-review/SKILL.md` owns that), for a bug hunt, or when the user wants code changed rather than assessed.

---

## Execution

### 1. Establish the coverage contract

Inventory every identifiable subsystem in the repository. Give each one:

- a **stable ID** (`S1`, `S2`, …) and a descriptive name;
- an **exact ownership boundary** — the paths and concerns it owns, written so two rows can never both claim the same file;
- its **key implementation files**;
- the relevant **public interfaces, major call sites, and tests**;
- a **status**: `queued`, `in review`, `recommend`, or `skip`.

Cover frontend, backend, shared infrastructure, platform bridges, generated-contract ownership, and test / tooling infrastructure wherever they are materially relevant to the audited codebase.

Create one **canonical scratchpad** carrying the whole audit state: the subsystem inventory, confirmed opportunities, explicit skip decisions, cross-cutting patterns, duplicates and superseded findings, final priorities and dependencies, and an audit log of what ran when.

**The inventory is the coverage contract.** A broad catch-all row (`S9 — everything else`) proves nothing and is itself a coverage defect: split it into real boundaries before any review starts.

### 2. Run bounded subsystem reviews

Give every worker exactly **one** subsystem with an exact, non-overlapping ownership boundary.

- **Use fresh read-only workers where the host provides them.** When a subagent / Task facility is available, dispatch one per subsystem, keep concurrency bounded to the number of lanes you can actively coordinate, wait on them through **one** consolidated wait rather than polling each, do not interrupt a worker merely for being slow, and close each worker once its result is harvested.
- **When no subagent facility is available, run the reviews sequentially inline** — one subsystem at a time, in inventory order, carrying no findings from the previous subsystem into the next beyond what the scratchpad records. Sequential execution changes the wall-clock cost, never the coverage contract: every row is still reviewed, and no row may be skipped for being expensive.

Every worker receives this brief:

> Review the assigned subsystem for **at most two** materially useful simplifications in its data structures, state representation, or organizing model. Inspect its implementation, its public interfaces, its major call sites, and its existing tests. Stay inside the assigned ownership boundary — you may *identify* a cross-subsystem concern, but never expand scope to solve it.

**Look for:**

- scattered booleans or nullable fields that permit invalid combinations, and should become a state machine or a discriminated union;
- repeated assumptions about an object's shape that want a single shared typed model;
- duplicated branching that a small map, registry, reducer, or command model would remove;
- unclear ownership of state or behavior that a small module boundary would settle;
- repeated scans, transformations, or lookups where a more appropriate collection or index materially simplifies the behavior;
- lifecycle, concurrency, or async states whose representation permits stale or contradictory state.

**Do not force an abstraction.** Boring local code that is already clear stays. A recommendation is **not** warranted for stylistic consistency, hypothetical extensibility, a minor line-count reduction, or moving existing branching behind a new type — the last one relocates complexity instead of removing it, and is the single most common false positive in this audit.

Return at most two opportunities. When nothing clearly clears the bar, return **skip** — a skip is a completed review, not a failure.

For every recommendation, provide all eight fields:

1. **Verdict** — `recommend` or `skip`.
2. **Evidence** — exact file and line references.
3. **Current complexity or invalid states** — what the present representation allows that it should not.
4. **Proposed representation and why it is simpler** — not merely different.
5. **Smallest credible implementation scope** — affected files and interfaces.
6. **Regression risks and migration concerns.**
7. **Existing and additional validation required.**
8. **Confidence** — `high`, `medium`, or `low`.

### 3. Validate and synthesize

The coordinator **independently verifies every finding against the current repository before accepting it** — read the cited lines, do not trust the worker's summary of them.

- **Reject, narrow, or demote** a recommendation that is vague, duplicates another finding, misreads intentional semantics (check the project-local instructions loaded in Constraints), or merely relocates complexity.
- **Record every skip as completed coverage** — the inventory row moves to `skip` with its reason, and the audit is that much closer to done.
- **Deduplicate overlapping findings** and assign each accepted recommendation to exactly **one** authoritative subsystem.
- Keep opening bounded review batches until every inventory row has reached `recommend` or `skip`.

### 4. Audit the audit

Before finishing, run fresh independent passes over the audit's own output:

- **repository coverage** — is any subsystem or boundary missing?
- **duplication and ownership overlap** — does one finding appear under two rows?
- **materiality and over-abstraction** — would a reader call any accepted recommendation over-engineered?
- **schema completeness** — does every finding carry all eight fields?
- **dependency-aware priority ranking** — is the order internally consistent?

When the coverage pass finds a real omission, **add an explicit subsystem row and audit it**. Never hide the gap by broadening a boundary that was already marked complete — that converts a known omission into a false claim of coverage.

Rank the final recommendations by concrete impact, confidence, implementation effort, blast radius, and prerequisites, then name the **best first implementation slices**.

---

## Output Format

Return one Markdown report:

```markdown
## Codebase simplification audit

**Scope:** <repository / area audited>
**Subsystems:** <N total — R recommend, S skip>
**Execution:** <parallel workers | sequential inline>

### Ranked recommendations

1. **<title>** — `<subsystem ID>` · confidence `<high|medium|low>` · effort `<S|M|L>` · blast radius `<narrow|wide>`
   - **Evidence:** `<file:line>`, `<file:line>`
   - **Current complexity / invalid states:** <what the representation allows today>
   - **Proposed representation:** <the target model, and why it is simpler>
   - **Smallest scope:** <affected files and interfaces>
   - **Risks and migration:** <regressions, ordering constraints>
   - **Validation:** <existing tests that cover it, plus what must be added>
   - **Depends on:** <recommendation number, or `none`>

### Best first slices

<the 1–3 recommendations to implement first, and why they unblock the rest>

### Coverage

| ID | Subsystem | Boundary | Status | Note |
|----|-----------|----------|--------|------|
| S1 | … | … | recommend | … |
| S2 | … | … | skip | <why nothing cleared the bar> |

### Cross-cutting patterns

<patterns that appeared in more than one subsystem, each naming the rows it spans>

### Audit-the-audit result

<what the five verification passes found, and what was added or demoted because of them>
```

Omit a section only when it is genuinely empty — an audit with no cross-cutting pattern drops that heading rather than rendering `none`. The **Coverage** table is never omitted: it is the proof that the contract was honoured.

---

## Principles

- Coverage is a contract, not an aspiration — an unreviewed row is an open audit.
- A skip is a result. Most subsystems in a healthy codebase should produce one.
- Verify every finding against the code yourself; a worker's confidence is an input, never a verdict.
- Simpler means fewer representable invalid states, not fewer lines.
- Relocating complexity behind a new type is not a simplification.
- Rank by what unblocks the most work, not by what is easiest to describe.

---

## Done when

- Every identifiable subsystem has been reviewed.
- Every subsystem carries a recommendation or an explicit skip.
- Every finding carries complete evidence, scope, risk, and validation fields.
- Duplicates and weak abstractions have been removed.
- Priorities and dependencies are internally consistent.
- The scratchpad has been deleted and the report returned.
- **The repository remains unchanged** — no file edited, no test run, nothing committed or pushed.
