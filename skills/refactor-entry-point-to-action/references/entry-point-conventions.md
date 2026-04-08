# Entry Point Conventions

## What counts as an entry point

- Controller methods
- Job `handle()` methods
- Command `handle()` methods
- Listener `handle()` methods
- Livewire component action methods (e.g. `save()`, `submit()`, `delete()`)

## Thin entry point rule

- The entry point method must become thin and only delegate to an Action using direct invocation syntax `$action($params)`.
- Do not keep business branching or orchestration in the entry point method.

## Livewire-specific rules

- Livewire components are entry points: component action methods must delegate to Action classes.
- The component class lives in `app/Livewire/` with a separate Blade view in `resources/views/livewire/`.
- Single-file (Volt) components are forbidden.

## Backward compatibility

- Method signatures and returned response format must stay backward compatible after refactoring.
- Keep account/multitenancy scope intact in all delegated calls.

## Scope of change

- Preserve behavior: refactor orchestration location, not business result.
- Do not change unrelated behavior while refactoring.
