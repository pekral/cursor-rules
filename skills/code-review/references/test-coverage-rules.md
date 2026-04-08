# Test Coverage Rules

## Coverage target

- Current changes must be covered by tests with 100% coverage.
- Coverage for changed files only (target 100% for changes). Run tests only for changed files.

## Test quality standards

- New code is tested: arrange-act-assert; error cases first; descriptive names; data providers via argument; mock only external services.
- **Prefer partial mocks** over full mocks — flag full mocks as **Minor** when a partial mock would suffice.
- Review tests as thoroughly as production code.

## Missing test identification

- Identify missing test variations.
- For new or changed behavior, suggest concrete test scenarios where coverage is missing or unclear (e.g. "Unit: method X with null/empty input"; "Integration: POST without auth must return 401"). This supports testing readiness alongside coverage metrics.

## Laravel-specific

- Prefer `Http::fake()` over Mockery for HTTP client mocking.
