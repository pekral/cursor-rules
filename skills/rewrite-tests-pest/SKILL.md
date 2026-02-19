---
name: rewrite-tests-pest
description: "Rewrites existing tests to PEST syntax following project conventions. Ensures DRY principles, uses data providers, maintains 100% coverage, and verifies test functionality."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).

**Steps:**
- For tests that do not use PEST syntax, I want you to rewrite them in PEST syntax.
- Follow the rules for writing tests.
- Correct DRY, use data providers, and try to write tests as simply as possible.
- After creating or modifying tests, check that they are not flaky.
- Tests must have 100% coverage.
- After writing the tests, verify that they are functional and follow the rules.
