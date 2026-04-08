# Type Safety Rules

## Spatie DTOs

- Use Spatie DTOs (Spatie Laravel Data) instead of arrays (except Job constructors)
- Use PHP attributes for property mapping — never override `from()` solely to rename keys
- Apply `#[MapInputName(SnakeCaseMapper::class)]` at class level for snake_case-to-camelCase input mapping, or `#[MapName(SnakeCaseMapper::class)]` when the DTO is also serialized to output
- Custom named static constructors (e.g. `fromModel()`, `fromRequest()`) are allowed for domain-specific data transformation

## `?array` is Forbidden

Any use of `?array` as a type hint must be replaced with a typed collection, DTO, or explicit `array<Type>|null`. Vague nullable arrays hide structure and break static analysis.

## PHP Array Key Type Safety

When refactoring associative arrays with dynamic keys, apply safe key strategies:
- Use stable prefixed keys (`'user:' . $id`, `'postal:' . $postalCode`, `'ext:' . $externalReference`)
- Prefer a dedicated collection or value object when the key is domain-significant
- Prefer `list<T>` when the structure is a list, not a map
- Prefer explicit validation or normalization before using external values as array keys
- Where relevant, prefer `array<non-decimal-int-string, T>` over misleading `array<string, T>`
