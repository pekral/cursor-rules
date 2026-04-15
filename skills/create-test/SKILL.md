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
- Do not modify production code unless strictly required

---

## Execution

### 1. Analyze Context
- Locate existing tests
- Identify missing coverage for changed code

### 2. Create or Update Tests
- Prefer updating existing tests
- Create new tests only if necessary
- Follow project conventions and helpers

### 3. Ensure Coverage
- Cover all changed code paths
- Include:
    - happy paths
    - edge cases
    - regression scenarios

### 4. Validate
- Run relevant tests
- Ensure deterministic behavior
- Remove flakiness

---

## Output

- Created or updated test files
- Coverage status for current changes

---

## Principles

- Prefer updating existing tests over creating new ones
- Keep tests simple and deterministic
- Cover behavior, not implementation
- Focus on changed code only
- Follow project test conventions strictly
- Prefer minimal tests for maximum coverage
- Avoid duplication across test cases
- Keep tests readable and maintainable
