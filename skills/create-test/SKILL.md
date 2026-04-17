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
- Run relevant tests after each change and confirm they pass
- Ensure deterministic behavior
- Remove flakiness

### 5. Verify Coverage
- Ensure 100% code coverage for all changed or added code paths
- If coverage tooling exists, run it and verify the result

### 6. Code Style
- Run project fixers in dry-run mode on changed test files
- Fix any code style violations

### 7. Test Review
- Run a quick code review of the created/updated tests against `@rules/code-testing/general.mdc`
- Fix any findings before finalizing

---

## Output

- Created or updated test files
- Coverage status for current changes (must be 100%)
- Test review result

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
