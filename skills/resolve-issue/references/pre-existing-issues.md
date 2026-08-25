# Pre-existing issue handling

Canonical procedure referenced from `@skills/resolve-issue/SKILL.md`. It governs problems that are **unrelated to the current assignment** but already present in the files the in-scope work forces you to open.

While reading and modifying the files required for the in-scope work, you may encounter problems that are **unrelated to the current assignment** but were already present in those files. The following categories qualify as pre-existing issues that must be fixed in this PR:

- **Bugs** — incorrect logic, broken edge cases, null-dereference risks, race conditions, or runtime errors that exist before this task.
- **Project-rule violations** — code that contradicts any rule listed in this skill's *Constraints* block (`@rules/php/core-standards.mdc`, `@rules/laravel/*`, `@rules/sql/optimalize.mdc`, etc.) or any other rule under `.claude/rules/`.
- **Security vulnerabilities** — anything `@rules/security/backend.md`, `@rules/security/frontend.md`, or `@rules/security/mobile.md` would flag (injection, missing authn/authz, unsafe deserialization, sensitive-data exposure, …).
- **Unnecessary comments** — comments / PHPDoc already sitting in the region you are changing that carry no information the code does not already give: narration of the statement below, a restatement of the signature, a redundant type docblock the native types already carry, commented-out code, a section banner, a changelog note, or a comment that no longer matches the code. Delete them per `@rules/php/core-standards.mdc` *Documentation* (*The codebase's default state is no comments*); keep only what clears the bar there — the *why*, a domain definition, a navigation pointer, or logic that is genuinely complex and cannot be made clearer by rewriting it. When a comment was compensating for an unclear symbol, rename or extract instead of deleting blind, and when a comment's value is genuinely unclear, keep it and name it in the PR rather than removing it.

Rules:

1. **Do not silently ignore** a pre-existing issue you encountered in a file you had to read for the in-scope work — fix it in this PR.
2. **Do not expand scope** by actively scanning unrelated files for additional pre-existing issues. Limit attention to files already touched by the in-scope changes (or their direct dependencies you must read to understand the change).
3. Land each pre-existing fix in its **own separate commit** inside the same PR:
   - Use a Conventional Commits subject per `@rules/git/general.mdc`: `fix(<scope>): pre-existing — <description>` for bugs and security, `refactor(<scope>): pre-existing — <description>` for rule violations without behavior change.
   - The `pre-existing — ` prefix is mandatory so reviewers can identify these commits at a glance (e.g. `fix(user): pre-existing — null check before dispatching welcome mail`).
   - **Test coverage workflow depends on the commit type:**
     - `fix(<scope>): pre-existing — …` (bug, security) — add the regression test in the **same commit** as the fix; the test must fail before the fix lands and pass after.
     - `refactor(<scope>): pre-existing — …` (project-rule violation, behavior-preserving) — apply `@rules/refactoring/general.mdc` *Test Coverage Contract*: when the target lines are below 100% coverage, author a dedicated `test(<scope>): cover <area> before pre-existing refactor` commit **before** the refactor commit, and do **not** modify pre-existing tests inside the refactor commit (mechanical renames forced by the refactor itself stay exempt and must be flagged in the commit body).
     - `refactor(<scope>): pre-existing — remove redundant comments in <area>` (unnecessary comments) — the deletion touches **no executable line**, so it neither needs a regression test nor triggers the *Test Coverage Contract*'s pre-refactor coverage commit; author no test for it. Keep the commit **comment-only** — the moment a deletion requires a rename, an extraction, or any other code change to stay readable, that is a different pre-existing item and belongs in its own `refactor(…)` commit under the contract's normal coverage rules.
   - Either way, pre-existing fixes follow the same 100% coverage rule on changed lines as in-scope changes (step 16) — with the comment-only deletion above as the single exception, since it adds and modifies no executable line to cover.
4. Order pre-existing fix commits **before** the in-scope commits in the commit plan from the previous section, so they form an independently revertable base. Update the recorded commit plan to include them before starting implementation.
5. If a pre-existing issue is **non-trivial** (would significantly expand the PR, requires architectural decisions, or affects shared infrastructure beyond the touched files), do **not** fix it inline. Move it to the *Out of scope (deferred)* group from step 7 and surface it under the PR's `## TODO` section with a one-line reason for deferral.
