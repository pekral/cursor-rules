---
name: test-driven-development
description: "Use when implementing a feature or bugfix with strict TDD. Enforce failing-test-first, minimal implementation, and safe refactoring."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/code-testing/general.mdc`
- Follow test conventions from `@skills/create-test/SKILL.md`

## Core principle
If you did not watch the test fail, you do not know whether it tests the right thing.

## Iron law
`NO PRODUCTION CODE WITHOUT A FAILING TEST FIRST`

## Use when
- Implementing a new feature
- Fixing a bug
- Changing behavior
- Refactoring code that should remain behaviorally stable

## Required cycle

### 1. RED
Write one minimal test for the next behavior.
- Keep the test focused and readable
- Prefer real code paths; mock only where appropriate by project testing rules
- Do not generate `covers()`

### 2. VERIFY RED
Run the test and confirm:
- it fails
- it fails for the expected reason
- it is not failing because of syntax, setup, or typo issues

If the test passes immediately, it does not prove the new behavior.

### 3. GREEN
Write the smallest production change needed to make the test pass.
- Do not add extra features
- Do not broaden scope
- Do not refactor unrelated code yet

### 4. VERIFY GREEN
Run the relevant tests and confirm:
- the new test passes
- affected existing behavior still passes

### 5. REFACTOR
Only after green:
- remove duplication
- improve naming
- simplify code
- keep behavior unchanged

### 6. REPEAT
Move to the next behavior and repeat the cycle.

## Bug-fix rule
Never fix a bug without first writing or updating a test that reproduces it.

## Scope control
- Fix obvious blocking issues only when necessary for safe implementation
- Keep unrelated cleanup out of scope unless it is trivial and low risk

## Done when
- Every implemented behavior is backed by a test
- Each new test was observed failing before implementation
- Production code was added only to satisfy failing tests
- Changed behavior, edge cases, and failure paths are covered
- Relevant tests pass
- Refactoring did not introduce new behavior
