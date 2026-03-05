---
name: rewrite-tests-pest
description: "Rewrites existing tests to PEST syntax following project conventions. Ensures DRY, uses data providers, maintains 100% coverage, and verifies test functionality. Use when converting PHPUnit or other test styles to PEST. Do not use for writing new tests from scratch (use create-test) or for non-PHP/PEST projects."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraints**
- Load all rules from `.cursor/rules/**/*.mdc` before starting.

**Steps**
1. Load all rules for the cursor editor from `.cursor/rules/**/*.mdc`.
2. For tests that do not use PEST syntax, rewrite them in PEST syntax.
3. Do not generate the `covers()` method. Follow the project rules for writing tests.
4. Apply arrange–act–assert; cover error cases first.
5. Before writing tests, analyze the abstractions used in the tests and use helper methods when they simplify the code.
6. Do not use the `describe()` function. Write tests at the top level with `it()` or `test()` only; do not wrap them in `describe()` blocks.
7. If there are shared helper functions (e.g. `bindSparkpostMailerNever($this->app)`), define them in the `Pest.php` file.
8. If a PEST test must call a method from an abstract class, use the notation `test()->methodName()`.
9. Apply DRY; use data providers; keep tests as simple as possible.
10. After creating or modifying tests, verify they are not flaky.
11. Analyze the created tests and any similar tests that can be simplified with data providers; then update them.
12. Ensure 100% coverage. After writing the tests, verify they are functional and follow the rules.
