# Action-Pattern Refactoring Rules

## When to Inline Service/Facade Methods

During issue resolution, if an Action calls a Service or Facade method that meets **all** of the following criteria:

1. The method is used **only once** in the entire codebase
2. The call originates from an Action class

Then:
- Move the business logic from that Service/Facade method directly into the Action
- Remove the original Service/Facade method

## Rationale

Single-use Service/Facade methods add unnecessary indirection. Inlining them into the Action simplifies the call chain and improves readability without losing structure.

## When NOT to Inline

- The method is called from multiple locations
- The method is part of a public API contract
- The method contains shared infrastructure logic (logging, caching, transactions) that should remain centralized
