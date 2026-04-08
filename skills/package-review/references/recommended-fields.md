# Recommended Fields

These fields are not strictly required but improve discoverability, usability, and maintainability.

## `keywords`
- Should contain searchable terms relevant to the package
- Use lowercase, no duplicates
- Aim for 3-8 keywords

## `homepage`
- Should be a valid, reachable URL
- Typically points to project documentation or repository page

## `support`
- Should include at least one of: `issues`, `source`, `docs`
- URLs must be valid and reachable
- `issues` should point to the issue tracker
- `source` should point to the repository

## `require-dev`
- Development dependencies (testing, static analysis, code style) should be listed here, not in `require`
- Common expected entries: PHPUnit, PHPStan, PHP_CodeSniffer, or equivalents

## `scripts`
- Useful composer scripts for common tasks (test, lint, analyze)
- Should reference tools listed in `require-dev`
- Example: `"test": "phpunit"`, `"analyse": "phpstan analyse"`
