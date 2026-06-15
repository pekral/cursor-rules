---
name: class-refactoring
description: Use when refactor PHP classes to improve structure, readability,
  and maintainability while preserving behavior
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Class Refactoring

## Purpose
Improve code structure and quality without changing behavior.

Focus on:
- clarity
- separation of concerns
- testability
- maintainability

---

## Modes

This skill runs in one of two modes, selected by the caller via `MODE` (default `apply`):

- **`apply` (default)** — full refactoring: modify code, author the pre-refactor coverage commit, run fixers / checkers, and chain the After Completion review. Every step below behaves as written unless it is explicitly flagged for `MODE=cr`.
- **`cr` (read-only lens — invoked by `@skills/code-review/SKILL.md`, `code-review-github`, `code-review-jira`)** — **never modify code, never author tests, never stage / commit / push, never run fixers or checkers, and never chain any After Completion review.** Scope the analysis to the lines added or modified by the PR diff and return the refactoring opportunities as markdown only, for the CR to fold into its Refactoring (DRY / tech debt) and Refactoring proposals sections. Every "apply / extract / split / consolidate" instruction below is emitted as a written proposal, not applied to code; the Test Coverage Gate becomes a read-only audit (report coverage gaps as findings, do not author tests).

---

## Constraints
- Apply @rules/refactoring/general.mdc — shared definition of refactoring, recommended incremental process, and "no big-bang rewrite" rule.
- Apply @rules/php/core-standards.mdc
- Apply @rules/php/dependency-selection.mdc — when the refactor proposes extracting behavior into an external Composer package or replacing a hand-rolled helper with a library, run the Activity gate + Compatibility gate from that rule before recommending the dependency. A refactor that adopts an archived / abandoned / branch-pinned package is rejected on the spot.
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Apply @rules/code-testing/general.mdc
- Never change behavior
- Keep public API stable unless explicitly required

---

## Execution

### Read, Map & Verify before refactoring (mandatory pre-flight)

> **`MODE=cr`:** perform Read and Map read-only to ground the proposals in the real code; Verify is the audit you already run. Do not modify code.

Reading, mapping, and verifying come first; refactoring comes last. This pre-flight is **blocking** — do not edit a single line of production code until all three steps pass, and never act on an assumption you have not confirmed by reading the code.

1. **Read** — open and read the actual class being refactored and the code it depends on (callers, called methods, related tests, configuration). Confirm what the code does by reading it, not by guessing from names.
2. **Map** — map the change's blast radius: every call site and caller of the touched code, the data-flow paths through it, the public API consumers, and the existing helpers / Services / Actions / layers to reuse instead of reinventing.
3. **Verify** — check your assumptions against the real code and its observed behavior before deciding the highest-impact refactoring. If reading and mapping contradict the task framing, stop and surface the discrepancy instead of refactoring on a wrong premise.

Only after Read, Map, and Verify are complete may the Test Coverage Gate and the refactor proceed.

### Test Coverage Gate (mandatory pre-flight — issue #493)

> **`MODE=cr`:** do not write tests or commits. Run the coverage check read-only and report any target lines below 100% coverage as a refactoring finding (a refactor cannot land safely without them) — then continue the analysis. The steps below that author tests / commits apply to `MODE=apply` only.

**The gate is blocking.** Refactoring may not edit a single line of production code until tests for the target lines reach 100% coverage. Satisfy the **Test Coverage Contract** defined in `@rules/refactoring/general.mdc`:

1. Verify coverage of the *current* code that the refactor will touch, using the project's available coverage tooling scoped to those files (per `@rules/php/core-standards.mdc` Testing section). Every line, branch, and condition must already be at 100%.
2. **If coverage is below 100% on the target lines, stop and write the missing tests first.** Use `@skills/create-test/SKILL.md` to author them; commit them in a dedicated `test(scope): cover <area> before refactor` commit per `@rules/git/general.mdc` Allowed Types. The pre-refactor coverage commit and the refactor commit are **always two separate commits** — never squash them and never mix new tests into the refactor commit.
3. The pre-refactor tests are the **behavior-preservation contract** for the refactor. Their **assertions must continue to pass unchanged** through the refactor commit, end to end. If a pre-existing assertion would have to change to make the refactor green, the change is **no longer a refactor** — it is a behavior change and must be split into a separate commit with its own justification (typically a `feat(scope): …` / `fix(scope): …` commit, not the refactor commit).
4. Only after the coverage gate is green and the assertions are confirmed stable may the refactor proceed.

### Refactoring steps

- Analyze the class and identify the highest-impact refactoring.
- Follow the incremental process from `@rules/refactoring/general.mdc` (stabilize → identify entry points → introduce Action pattern → split responsibilities → modernize → DRY → concurrency). Never propose a big-bang rewrite.
- Fix any obvious pre-existing bugs before refactoring (separate commit).
- Apply focused refactoring **strictly per the applied rules** — `@rules/refactoring/general.mdc`, `@rules/php/core-standards.mdc`, `@rules/code-testing/general.mdc`, and (for Laravel projects) `@rules/laravel/laravel.mdc` + `@rules/laravel/architecture.mdc` + `@rules/laravel/filament.mdc` + `@rules/laravel/livewire.mdc`. The refactor rewrites the existing code into the **target architecture** (Action / Service / Repository / ModelManager / Data Validator / Data Builder / DTO per project rules) and the **target code-style** (naming, structure, parameter count, nesting, design principles). Anything that would deviate from the rules is rewritten until it complies; do not invent ad-hoc structure outside the rule set.
- Concrete refactoring activities:
  - simplify structure
  - reduce complexity
  - improve naming
  - extract responsibilities where needed
- **Test assertion logic must not change during the refactor.** The pre-refactor coverage commit fixed the contract; the refactor commit changes structure only. Pre-existing assertions, expected return values, expected exceptions, expected persisted state, and expected emitted events stay byte-for-byte the same — they are the proof that behavior is preserved. The only allowed test edits in the refactor commit are mechanical renames forced by the refactor itself (e.g. namespace move, constructor argument order forced by an extracted DTO), and they must be flagged in the commit body. If an assertion would have to change to make the refactor green, treat that as a signal that you are no longer refactoring and split the behavior change into its own commit instead. New tests that cover newly introduced code paths belong in a separate `test(scope): …` commit *after* the refactor.
- **After the refactor — re-verify coverage stayed 100%.** Once the refactor commit is in place, run the coverage tooling again scoped to the refactored files and confirm every changed line, branch, and condition is still exercised and the pre-existing assertions still pass unchanged. Coverage must **remain** 100% — a refactored line that is no longer covered means the refactor introduced an untested path; fix the path (it is usually dead code or a new branch), never restore the number by editing the pre-refactor tests. This is the apply-mode enforcement of step 4 of the **Test Coverage Contract** in `@rules/refactoring/general.mdc`.
- Avoid unnecessary changes outside the scope.
- Prefer small, safe transformations over large rewrites.

---

## Refactoring Guidelines

- Ensure single responsibility per class.
- Separate orchestration from business logic.
- **Speculative interfaces:** Collapse project-owned `interface` types that have neither at least two non-test consumers nor at least two non-test implementations back into their concrete class. Test doubles, mocks, and fakes do not count toward either threshold. Implementing a framework or vendor interface (e.g. `ShouldQueue`, `HasLabel`, `Arrayable`) is always allowed. Keep a single-implementation, single-consumer project interface only when there is a documented architectural reason — a published package API surface or a plugin extension point with a written contract. See `@rules/php/core-standards.mdc` Design Principles.
- **Business Logic Layers (Laravel projects only):** Business logic must live in exactly one of the seven allowed class types — **Actions**, **Model Services**, **Repositories**, **ModelManagers**, **Data Validators**, **Data Builders**, or an **Eloquent model** (last one only for simple, self-contained own-data methods — see the boundary in `@rules/laravel/architecture.mdc` "Business Logic Layers"). When a class file contains business logic that spans more than one of these layers, contains business logic that does not fit any of them, or holds an Eloquent model method that crosses the simple-logic boundary (calls services / repositories / model managers, issues new queries, performs persistence side effects, or coordinates multiple entities), propose a refactoring that splits the responsibilities into dedicated classes from the seven-layer list. Surface every detected violation in the refactoring plan with the target layer for each extracted responsibility.
- Replace per-row DB queries inside loops with batch operations per `@rules/sql/optimalize.mdc` "Batch over per-row operations" — ModelManager `batchUpdate` / `batchInsert`, `whereIn(...)->delete()`, or a single bulk read keyed in memory. Keep per-row work only when an explicit side-effect dependency between iterations cannot be batched.
- Remove duplication (DRY).
- Before modifying code, enumerate every place that modifies data before it is saved or passed downstream (DTO mapping, payload shaping, key renaming, default fallbacks, format normalization, business-driven derivation). Surface the list in the refactoring plan and consolidate duplicates into the canonical layer per `@rules/laravel/architecture.mdc` Data Modification (DRY) section (Data Builder, DTO named constructor, Data Validator, ModelManager, Repository).
- Prefer small, focused methods.
- **Simplicity First.** A refactor must leave the touched code at least as simple as it found it — never trade structural clarity for unrequested flexibility. Reject any proposed step that adds an abstraction for code with a single call site, introduces a configurability / extension point not justified by an existing caller, adds error handling for impossible scenarios (catching exceptions the call surface cannot throw, defensive guards on internal values the caller already validates, fallbacks for unreachable branches), or expands a method's line count without an architectural justification anchored in `@rules/php/core-standards.mdc` Design Principles or, on Laravel projects, `@rules/laravel/architecture.mdc`. When two refactoring options preserve behavior equally well, pick the shorter, less layered one ("if you write 200 lines and it could be 50, rewrite it"). Reuse existing helpers / Services / Actions / Repositories before extracting a new class. In `MODE=cr`, surface every such speculative addition the PR diff introduces as a refactoring proposal rather than a code change.
- Extract intention-revealing private methods when it improves clarity.
- Avoid deep nesting and complex conditionals.
- Keep method signatures clear and minimal.
- **Method parameter count (>4 → DTO):** when a method, function, closure, constructor, `__invoke()`, or other callable crosses the threshold, propose extracting a dedicated typed DTO and passing it as a single argument, per `@rules/php/core-standards.mdc` Structure section (parameter counting rules, exemption list, and required fix are defined there).

---

## Laravel Context (if applicable)

- Delegate business logic to Actions and Services.
- **Pass-through Actions (Action pattern).** Per `@rules/laravel/architecture.mdc` *Pass-through Action rule*, an Action whose entire `__invoke()` body is a single delegating call to one Service / Facade / Model Service method — with no orchestration of its own (no validation delegation, no DTO / data transformation, no coordination of multiple collaborators, no extra business step, no return-value reshaping) — is a redundant indirection layer and must be collapsed during the refactor. Detect every such pass-through Action touched by the refactor and resolve it one of two ways: (1) if the wrapped Service / Facade method is used **only once** in the codebase, move its logic into the Action and delete the method (the **Single-use Service/Facade method rule**), so the Action does real work; (2) if the method is **reused** elsewhere, remove the Action entirely and rewrite the entry point to call the Service / Facade method directly (`$action($payload)` → `$service->method($payload)`), updating every call site. In `MODE=cr`, emit each pass-through Action as a written refactoring proposal (target resolution + every call site that must change) rather than applying the change.
- Do not place business logic in controllers or Livewire components.
- Use existing query scopes instead of duplicating conditions.
- Prefer DTOs over raw arrays when the project uses them.
- Keep Repositories limited to basic, reusable queries. When refactoring uncovers a feature-specific query method on a Repository, move it to a Service (single-model) or an Action (cross-model / cross-feature) that composes basic Repository methods (see `@rules/laravel/architecture.mdc` Repositories and ModelManagers section).
- **Livewire / Blade view splitting.** When the refactor touches a Livewire component or Blade view (`app/Livewire/**/*.php`, `resources/views/livewire/**/*.blade.php`, `resources/views/**/*.blade.php`), analyze its HTML as a tree of UI concerns per `@rules/laravel/livewire.mdc` *HTML / Blade Layout Splitting*. Walk every trigger in that section (repeated markup, >150 Blade lines, self-contained `wire:*` cluster, self-contained data shape, cross-page reuse, independent loading / empty / error state, distinct named UI concern) and propose an extraction for each match. Pick **Livewire** children only for blocks with their own state / lifecycle / server interaction; pick **Blade** components for stateless presentation — wrapping presentational markup in a Livewire component just to enable reuse is itself a refactoring finding. Every extracted component must satisfy the **Reusability contract** in that rule (typed input, one concern, no business logic, events not parent reach-through, independently renderable, correct tree placement, concern-based name). The layout split is a structural refactor — the **Test Coverage Gate** above applies in spirit: every rendered branch of the touched view (initial render, `wire:loading`, `@empty`, error banner, each `@if` / `@foreach` arm) must be exercised by a Livewire / Blade feature test committed before the layout refactor, and the same feature tests must stay green through the refactor commit unchanged. PHP `--coverage-clover` does not measure `.blade.php` line-by-line, so the binding gate is feature-test parity, not a numeric coverage percentage on the view file.

---

## Testing

- **`MODE=cr`:** this section is apply-mode only — the read-only lens audits coverage per the Test Coverage Gate note and reports gaps as findings; it never authors tests or commits.
- The **Test Coverage Gate** in the Execution section is the binding rule — pre-existing target lines must be at 100% coverage *before* the refactor, written into a dedicated `test(scope): cover <area> before refactor` commit per `@rules/refactoring/general.mdc` Test Coverage Contract.
- Inside the refactor commit, **assertion logic of pre-existing tests must remain unchanged**. Expected return values, expected exceptions, expected persisted state, expected emitted events, and expected side effects all stay identical — they are the behavior-preservation proof. Mechanical renames forced by the refactor itself (namespace move, constructor argument shape forced by an extracted DTO) are the only allowed test edits and must be flagged in the commit body. An assertion that has to change is a behavior change, not a refactor — split it out.
- New tests covering code paths introduced by the refactor go in a separate `test(scope): …` commit *after* the refactor.
- Prefer realistic tests over heavy mocking.

---

## Output

- **`MODE=apply`:**
  - Refactored code
  - Short explanation of changes:
    - what was improved
    - why it matters
  - Summary of test coverage impact
- **`MODE=cr`:** refactoring opportunities as markdown only (no code) — for each, the `file:line` on the PR diff, the structural problem in one sentence, the concrete consolidation step (target layer per `@rules/laravel/architecture.mdc`), and the rule reference it satisfies. The CR places in-scope items in its **Refactoring (DRY / tech debt)** section and out-of-scope structural problems in **Refactoring proposals**.

---

## Principles

- Preserve behavior — change how, not what
- Prefer clarity over cleverness
- Prefer simple solutions over complex abstractions
- Avoid over-engineering
- Improve only what is necessary

---

## Pre-push quality gates

> Skip this entire section in `MODE=cr` — a read-only lens pushes nothing.

- Discover available fixers and checkers (prefer Phing targets from `build.xml`/`phing.xml`; fall back to Composer scripts in `composer.json`)
- Run available fixers on all changed files and fix any violations
- Run available checkers/analyzers on all changed files and resolve all reported errors

## After Completion

> Skip this entire section in `MODE=cr` — the CR is the caller, so chaining back into it would recurse. Return the findings to the caller and stop.

- **Run the review inline.** Invoke `@skills/code-review/SKILL.md` directly in this skill's context, passing the refactor commit range plus the instruction to return Critical / Moderate / Minor findings with their reproducer fields. Do not dispatch the review as a subagent — run it sequentially in the current context.
- Resolve findings via `@skills/process-code-review/SKILL.md` (also invoked inline per its own contract).
