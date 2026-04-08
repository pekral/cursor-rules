# Action Pattern Refactoring

## Rule

During issue resolution, when refactoring code that follows the Action pattern:

If an **Action** calls a **Service** or **Facade** method that is used **only once** in the entire codebase, move the business logic from that Service/Facade method directly into the Action and remove the original Service/Facade method.

## Rationale

- Eliminates unnecessary indirection for single-use logic.
- Reduces the number of classes to maintain.
- Keeps the Action self-contained and easier to understand.

## When NOT to Apply

- The Service/Facade method is called from multiple locations.
- The method is part of a public API contract.
- Moving the logic would violate the single responsibility of the Action.
