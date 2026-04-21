## Previous CR Status

> Include this section only in follow-up reviews when a previous CR exists for the same PR. Omit entirely for first reviews.

| # | Finding | Status |
|---|---------|--------|
| 1 | Previous finding description | ✅ Resolved / ⏳ Deferred / ❌ Still open |

---

## Critical

1. [file:line] Description  
   Impact: ...  
   Fix: ...

## Moderate

1. ...

## Minor

1. ...

## Refactoring Proposals

If any reviewed code violates project rules (`@rules/php/core-standards.mdc`, `@rules/laravel/architecture.mdc`) or has clear structural issues that are **out of scope** for the current PR, propose a new issue for each refactoring opportunity:

1. **Title:** short, actionable issue title  
   **Scope:** affected file(s) or area  
   **Reason:** which rule or principle is violated and why it matters  
   **Suggested approach:** brief description of the expected refactoring

Only propose refactoring that is justified by defined rules or architecture — not stylistic preferences.
If no refactoring opportunities are found, omit this section.

**Summary: X Critical, Y Moderate, Z Minor**
