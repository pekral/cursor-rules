---
name: rewrite-tests-pest
description: "Use when rewriting existing tests to Pest syntax. Preserve behavior, follow project testing conventions, reduce duplication where helpful, and verify rewritten tests are deterministic and passing."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/code-testing/general.mdc`
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Do not generate `covers()`

## Use when
- Existing tests are written in PHPUnit-style syntax and should be rewritten to Pest
- You want to modernize tests without changing their intended behavior

## Required approach
- Preserve test intent and coverage of the rewritten behavior
- Keep tests deterministic and non-flaky
- Prefer simple, readable Pest syntax
- Use helper methods or datasets when they clearly reduce duplication
- Avoid reflection; prefer mocks or partial mocks when readable and effective
- Avoid branching in tests; prefer separate test cases or datasets instead

## Execution
1. Identify existing tests that should be rewritten to Pest syntax.
2. Analyze repeated setup and assertions before rewriting.
3. Rewrite tests to Pest syntax without changing covered behavior.
4. Use datasets/data providers where they simplify similar test cases.
5. Move broadly shared lightweight test helpers to `Pest.php` when it improves clarity and reuse.
6. If a Pest test needs to call a helper method defined on the test case for abstract-class scenarios, use `test()->methodName()`.
7. Keep tests structured and easy to read, preferably with clear arrange / act / assert flow.
8. Separate success and failure scenarios into distinct test cases where practical.
9. Run the rewritten tests and confirm they pass consistently.
10. Simplify nearby similar tests only when the cleanup is small, safe, and clearly improves maintainability.

## Post-rewrite validation
1. Run all rewritten tests and confirm they pass.
2. Verify 100% code coverage for all rewritten test paths — if coverage tooling exists, run it.
3. Discover available fixers and checkers (prefer Phing targets from `build.xml`/`phing.xml`; fall back to Composer scripts in `composer.json`).
4. Run available fixers on changed test files and fix any violations.
5. Run available checkers/analyzers on changed test files and resolve all reported errors.
6. Run a quick code review of rewritten tests against `@rules/code-testing/general.mdc` and fix any findings.

## Done when
- Target tests are rewritten to Pest syntax
- Rewritten tests preserve original intent and behavior
- Tests are deterministic and pass reliably
- 100% code coverage is verified for rewritten code paths
- Code style and quality checks pass (fixers and checkers ran clean)
- Test review passed with no findings
- Duplication is reduced where it meaningfully improves readability
- Shared lightweight helpers are extracted appropriately
- The rewritten tests follow project testing conventions
