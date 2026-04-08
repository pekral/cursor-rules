# Action Pattern Rules

## Core principles

- **All business logic is allowed only in classes that follow the Action pattern.**
- Mandatory flow: `Controller/Job/Command/Listener/Livewire Component -> Action -> ModelService -> Repository (read) / ModelManager (write)`.

## Class constraints

- New Action must be placed under `app/Actions/**` in a domain-specific subfolder.
- Action class must be `final readonly`.
- Action must expose exactly one public business entry point: `__invoke(...)` with explicit return type.
- Do not create multiple public business methods in an Action.

## Quality expectations

- Action must stay clean and simple: minimal orchestration surface, no duplicated branches, no dead paths.
- Action should be as optimized as possible for readability and runtime (avoid redundant mapping, calls, or temporary structures).
- No direct Eloquent queries and no `DB::` calls inside the Action.
- Action orchestrates only: data validator invocation, mapping, and delegation; heavy shared logic belongs to Services.

## Invocation convention

- Always use `$action($params)` to call Actions -- never use `$action->__invoke($params)`. PHP natively routes the call to `__invoke()`, making the explicit form redundant.

## Single-use Service/Facade method rule

If the Action calls a Service or Facade method that is used only once in the entire codebase, move the business logic from that Service/Facade method directly into the Action and remove the original Service/Facade method.

## BaseModelService pattern

When delegating to Services, ensure model-oriented services extend `BaseModelService` and implement `getModelManager()`, `getRepository()`, and `getModelClass()` (see `vendor/pekral/arch-app-services/examples/Services/User/UserModelService.php`). Services that do not primarily serve a single model must be refactored into Action pattern classes instead.

## PHPDoc requirements

- Add or update PHPDoc where needed so PHPStan can infer intent/types without ambiguity (especially DTO shapes, iterable generics, and non-obvious contracts).
