# Laravel-Specific Guidance

When the input is Laravel code, also inspect:

- Eager loading opportunities via `with()`
- `whereHas()` and nested relationship filters
- `withCount()` usage
- `chunk()` vs `cursor()` vs pagination
- Scopes that hide query complexity
- Repeated query patterns in loops
- Casts or accessors that trigger hidden queries
- Whether a repository or service builds inefficient dynamic filters

## Output format

If useful, the skill may provide both:

- A rewritten SQL query
- An improved Eloquent or query builder version

## Common Laravel pitfalls

- Using `whereHas()` without indexes on the related table's foreign key
- Calling `->get()` inside a loop instead of eager loading
- Using `withCount()` on large tables without supporting indexes
- Relying on `latest()` or `oldest()` scopes without a matching index on `created_at`
- Building dynamic query chains that prevent the optimizer from using indexes effectively
