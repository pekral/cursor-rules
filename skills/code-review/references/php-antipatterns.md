# PHP Antipatterns and Type Safety Rules

## `?array` is forbidden (Critical)

Any use of `?array` as a type hint is an error. Replace with a typed collection, DTO, or explicit `array<Type>|null`. Vague nullable arrays hide structure and break static analysis.

## PHP array key type safety (Moderate)

When reviewing associative arrays, check whether a supposed string key can actually become an integer key at runtime. PHP silently casts:
- Decimal integer strings like `'123'` to `123`
- `bool` to `0`/`1`
- `float` to truncated `int`
- `null` to `''`

Do not trust `(string) $value` alone as proof of safety.

### High-risk patterns to flag

- `$map[$id] = $value;`
- `$set[$value] = true;`
- `$grouped[$key][] = $item;`
- `$indexed[(string) $something] = ...;`

### Dangerous key sources

Request input, database values, CSV/XML/API data, `substr()`, `trim()`, `explode()`, casts, or values typed as `mixed`, `scalar`, `string|int`, `bool`, `float`, or `null`.

### Dangerous follow-up operations

`array_merge()`, `array_keys()`, `in_array(..., $keys, true)`, `array_key_exists()`, `isset($map[$key])`, `foreach ($map as $key => $value)` — when `$key` is later passed into a strict `string` parameter.

### When reporting

- Identify the exact risky key source
- Explain how PHP may cast it at runtime
- State the practical impact (overwritten entries, failed strict comparisons, unexpected reindexing, possible `TypeError`)
- Recommend the smallest safe fix first
- Suggest tests for: numeric-string keys, key collision after casting, strict lookups via `array_keys()`, `array_merge()` behaviour with casted keys

## Invokeable call syntax (Moderate)

If code calls an Action (or any invokeable class) via `->__invoke()` instead of direct invocation `$action(...)`, flag as **Moderate** and recommend the shorter form.

## DTO attribute syntax (Moderate)

If a Spatie Laravel Data DTO overrides `from()` solely to rename input keys, or uses manual array mapping instead of `#[MapInputName(SnakeCaseMapper::class)]` / `#[MapName(SnakeCaseMapper::class)]` attributes, flag as **Moderate** and suggest the declarative attribute approach.

Custom named static constructors (e.g. `fromModel()`, `fromRequest()`, `fromArray()`) that perform domain-specific data transformation beyond simple key renaming are a valid pattern and must NOT be flagged.
