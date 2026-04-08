# Common Bug Patterns

## Null Handling
- Accessing properties or methods on potentially null values without guards.
- Using `!` or `!!` to silence null warnings instead of handling the case.
- Missing null checks after `find()`, `first()`, or external API calls.

## Off-by-One Errors
- Loop boundaries using `<=` instead of `<` (or vice versa).
- Array slicing with incorrect start/end indexes.
- Pagination offset calculations that skip or duplicate records.

## Missing Validation
- User input passed directly to queries or business logic without validation.
- Missing type checks on external API responses.
- Assuming array keys exist without checking.

## Incorrect Conditions
- Inverted boolean logic (using `&&` instead of `||`).
- Comparing wrong variables or using wrong comparison operators.
- Early returns that skip necessary cleanup or side effects.

## Edge Cases
- Empty collections or arrays — code assumes at least one element.
- Zero or negative values in calculations — division by zero, negative prices.
- Unicode or special characters in string processing.
- Concurrent access — two requests modifying the same record simultaneously.

## Type Coercion
- PHP silently casting string keys to integers in arrays.
- Loose comparison (`==`) where strict (`===`) is needed.
- Truthy/falsy checks that fail for edge values (`0`, `""`, `"0"`, `null`).

## Resource Leaks
- Database connections not closed in error paths.
- File handles left open after exceptions.
- Event listeners or timers not cleaned up.
