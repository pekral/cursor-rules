---
name: create-test
description: Use when create or update tests to ensure full coverage for current changes
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Create Test

## Purpose
Create or update tests to cover current changes according to project conventions.

---

## Constraints
- Apply @rules/code-testing/general.mdc
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Do not modify production code unless strictly required — the only exception is the **Pre-existing issue handling** workflow below, which lands its production-code fixes in their own separate commits

---

## Execution

### 1. Analyze Context
- Locate existing tests
- Identify missing coverage for changed code

### 2. Create or Update Tests
- Prefer updating existing tests
- Create new tests only if necessary
- Follow project conventions and helpers
- **Place new test files per `@rules/code-testing/general.mdc` *Test Organization*** — the test file path mirrors the namespace of the SUT (e.g. `App\Service\Billing\InvoiceCalculator` → `tests/Service/Billing/InvoiceCalculatorTest.php`), the file name is `{ClassName}Test.php` (or `{ClassName}{Scenario}Test.php` for an extracted scenario file of the same SUT), and cross-cutting tests sit under an intent-named directory (`tests/Feature/<flow>`, `tests/Contract/<vendor>`, `tests/Integration/<area>`).
- **Name every `it()` / `test()` block to match the scenario the body asserts** — plain-language descriptions such as `it('returns zero for an empty cart')` or `test('throws InvalidArgumentException when the discount is negative')`. Never use placeholders (`it('it works')`, `test('test1')`, `test('happy path')`), method names (`test('calculate')`, `it('handles getUser')`), or descriptions that contradict the assertions. When changing what a test asserts, rename the description in the same change so the code-review test-organization gate passes downstream.

### 3. Ensure Coverage
- Cover all changed code paths
- Include:
    - happy paths
    - edge cases
    - regression scenarios

### 4. Validate
- Run relevant tests after each change and confirm they pass
- Ensure deterministic behavior
- Remove flakiness

### 5. Verify Coverage
- Ensure 100% code coverage for all changed or added code paths
- If coverage tooling exists, verify coverage **for the changed files only**, using the project's available coverage tooling (per the Coverage gate in `@skills/code-review/SKILL.md`) and verify the result. Do not gate on a project-wide coverage percentage — full-suite coverage is for release gates, not for verifying current changes. Delete any generated coverage report file once read so it is not accidentally committed.

### 6. Code Style and Quality Gates
- Discover available fixers and checkers (prefer Phing targets from `build.xml`/`phing.xml`; fall back to Composer scripts in `composer.json`)
- Run available fixers on changed test files and fix any violations
- Run available checkers/analyzers on changed test files and resolve all reported errors

### 7. Test Review
- Run a quick code review of the created/updated tests against `@rules/code-testing/general.mdc`
- Fix any findings before finalizing

### 8. Pre-existing issue handling

While writing tests, you may uncover problems that are **unrelated to the current change** but were already present in the code you had to read or exercise. The following categories qualify:

- **Bugs** — incorrect logic, broken edge cases, or runtime errors revealed by exploratory test runs, but already present before this task.
- **Project-rule violations** — code that contradicts any rule listed in this skill's *Constraints* block or any other rule under `.claude/rules/`.
- **Security vulnerabilities** — anything `@rules/security/backend.md`, `@rules/security/frontend.md`, or `@rules/security/mobile.md` would flag.

Rules:

1. **Do not silently ignore** a pre-existing issue you encountered in code you had to read or exercise to write the tests for the current change.
2. **Do not expand scope** by actively scanning unrelated files for pre-existing issues. Limit attention to files already touched or exercised by the current change.
3. Land each pre-existing fix (and its regression test) in its **own separate commit**, distinct from the test-coverage commit for the current change:
   - Use a Conventional Commits subject per `@rules/git/general.mdc`: `fix(<scope>): pre-existing — <description>` for bugs and security, `refactor(<scope>): pre-existing — <description>` for rule violations without behavior change.
   - The `pre-existing — ` prefix is mandatory so reviewers can identify these commits at a glance.
   - **Test coverage workflow depends on the commit type:**
     - `fix(<scope>): pre-existing — …` (bug, security) — add the regression test in the **same commit** as the fix; the test must fail before the fix lands and pass after.
     - `refactor(<scope>): pre-existing — …` (project-rule violation, behavior-preserving) — apply `@rules/refactoring/general.mdc` *Test Coverage Contract*: when the target lines are below 100% coverage, author a dedicated `test(<scope>): cover <area> before pre-existing refactor` commit **before** the refactor commit, and do **not** modify pre-existing tests inside the refactor commit (mechanical renames forced by the refactor itself stay exempt and must be flagged in the commit body).
4. The "Do not modify production code unless strictly required" constraint above is **overridden** for these fixes — the production-code change is the fix itself, and it lives in its own commit.
5. If a pre-existing issue is **non-trivial** (would significantly expand the change or requires architectural discussion), do **not** fix it. Surface it in the skill's output report as a deferred follow-up with the reason.

---

## Output

- Created or updated test files
- Coverage status for current changes (must be 100%)
- Test review result
- List of pre-existing fix commits (if any), each with a one-line rationale, plus any pre-existing issue deferred as a follow-up with the reason

---

## Principles

- Prefer updating existing tests over creating new ones
- Keep tests simple and deterministic
- Cover behavior, not implementation
- Focus on changed code only
- Follow project test conventions strictly
- Prefer minimal tests for maximum coverage
- Use data providers where they improve readability and reduce duplication
- Keep tests readable and maintainable
