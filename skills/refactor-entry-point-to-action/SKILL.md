---
name: refactor-entry-point-to-action
description: "Use when refactoring any entry point logic from a controller/job/command/listener into an Action class. Enforces Action pattern rules from project.mdc and related architecture rules."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- Read all architecture rules that define Action pattern requirements before writing any code.
- Preserve behavior: refactor orchestration location, not business result.
- In this iteration, do not report code review output to any third-party service.
- After generating or updating code, run immediate internal code review focused on architecture and fix findings ASAP.

**Use when:**
- A controller entry point (or job/command/listener/**Livewire component** entry point) contains orchestration logic and must be migrated to Action pattern.
- You want to run this skill manually in Cursor for a specific entry point method.

**Manual invocation in Cursor:**
- Call this skill and always include:
  - File path of the entry point class.
  - Expected target Action class name and domain folder (or ask to propose one).
  - Constraints if API response format/signature must remain unchanged.
- Input template:
  - `Refactor entry point <Class::method> in <path> to Action pattern.`
  - `Keep behavior and response contract unchanged.`
  - `Create/use Action in app/Actions/<Domain>/<ActionName>.php and wire entry point to delegate.`
  - `Respect project.mdc Action architecture rules.`

**Scripts:** Use the pre-built scripts in `@skills/refactor-entry-point-to-action/scripts/` for recurring operations. Do not reinvent these commands -- run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/run-quality-checks.sh` | Run lint, static analysis, and code style checks on changed PHP files |
| `scripts/run-tests.sh` | Run targeted or full test suite |
| `scripts/run-migrations.sh` | Run pending database migrations |

**References:**
- `references/action-pattern-rules.md` -- Action class constraints, single-use rule, BaseModelService pattern, invocation convention, PHPDoc requirements
- `references/data-validator-rules.md` -- Data Validator placement, class constraints, prohibited patterns in Actions
- `references/entry-point-conventions.md` -- what counts as an entry point, thin entry point rule, Livewire rules, backward compatibility
- `references/definition-of-done.md` -- completion criteria checklist, post-refactor verification steps

**Examples:** See `examples/` for expected output format:
- `examples/report-successful-refactor.md` -- clean refactor with no review findings
- `examples/report-refactor-with-review-findings.md` -- refactor that required fixes after internal review

**Steps:**
1. Analyze current implementation of the target entry point method and identify orchestration steps.
2. Create a dedicated Action (one use case = one Action) in the correct domain folder under `app/Actions/**` per `references/action-pattern-rules.md`.
3. Move orchestration from entry point method into Action `__invoke(...)`.
4. Keep reads in Repository and writes in ModelManager; if missing, introduce or reuse proper layer classes.
5. Extract any validation logic into a Data Validator per `references/data-validator-rules.md`.
6. Update entry point method to dependency-inject and call the Action per `references/entry-point-conventions.md`.
7. Keep account/multitenancy scope intact in all delegated calls.
8. Ensure method signatures and returned response format stay backward compatible.
9. Add or update PHPDoc to satisfy static analysis quality for touched PHP code.
10. Run an internal architecture-first code review of the generated changes (no third-party reporting in this iteration).
11. If the review finds critical or medium issues, fix them immediately and repeat the review until no such findings remain.
12. If PHP files changed, run `scripts/run-quality-checks.sh` and resolve all issues.
13. If new database migrations were created during the changes, run `scripts/run-migrations.sh` before running tests or creating a PR.
14. Add or update tests to fully cover touched logic and preserve behavior. Run `scripts/run-tests.sh` to verify.

**Do not:**
- Do not place validation logic (throwing `ValidationException`, calling `Validator::make()`) directly in Action classes -- use Data Validators.
- Do not keep business branching/orchestration in the controller method.
- Do not place Action classes outside `app/Actions/**`.
- Do not create multiple public business methods in an Action.
- Do not bypass Repository/ModelManager boundaries.
- Do not change unrelated behavior while refactoring.

**Output contract:** For each refactored entry point, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Entry point | Yes | Class and method that was refactored |
| Action created | Yes | Full path of the new or updated Action class |
| Data Validator | If applicable | Full path of the Data Validator class |
| Decision | Yes | `refactor complete` or `refactor blocked` |
| Review findings | Yes | Count of critical/medium findings and whether they were fixed |
| Quality checks | Yes | All passed / issues found |
| Changes summary | Yes | Brief list of what was moved, created, or inlined |
| Tests | Yes | List of test files created or updated |
| Confidence notes | If applicable | Caveats or assumptions (e.g., untestable path, scope limitation) |
| Next action | Yes | What should happen next |
