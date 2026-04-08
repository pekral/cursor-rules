# Severity Levels

Use exactly three severity levels for every finding. Assign each finding to one level.

## Critical

Issues that break functionality, cause data loss, introduce security vulnerabilities, or break existing features (regressions).

Examples:
- Breaking shared logic that affects other consumers
- Security vulnerabilities (SQL injection, XSS, auth bypass)
- Data loss or corruption risks
- Use of `?array` as a type hint (always Critical)

## Moderate

Issues that degrade quality, maintainability, or performance but do not break functionality immediately.

Examples:
- Missing error handling in important paths
- N+1 query problems
- Unnecessary complexity
- DRY violations
- DTO attribute syntax issues (using `from()` solely to rename keys instead of `#[MapInputName]`)
- Invokeable call syntax (`->__invoke()` instead of `$action(...)`)
- PHP array key type safety issues
- Missing test coverage for changed code

## Minor

Low-impact suggestions for improvement, style preferences beyond tooling, or small optimizations.

Examples:
- Using full mocks where partial mocks would suffice
- Minor naming improvements beyond what linters enforce
- Small optimization opportunities
- Missing test variations for edge cases

## Ordering

- Group output by severity: Critical first, then Moderate, then Minor.
- Within each group, rank issues by impact (highest technical debt first).
