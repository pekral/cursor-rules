# Pre-push Quality Gates

Before committing and pushing changes, run project fixers and checkers on changed files. Discover available tooling using this priority:

1. **Phing** — check for `build.xml` or `phing.xml` in the project root. If present, list available targets (`phing -l`) and use relevant fixer/checker targets.
2. **Composer scripts** — if Phing is not available, inspect `composer.json` `scripts` section for fixer and checker commands (e.g. `fix`, `check`, `build`, `pint-fix`, `phpcs-fix`, `rector-fix`, `pint-check`, `phpcs-check`, `rector-check`, `test:coverage`).

Run in this order:
1. **Fixers** — run all available fixers on changed files (e.g. code style, rector, normalize). Fix any issues they report.
2. **Checkers** — run all available checkers/analyzers on changed files (e.g. code style check, static analysis, audit). Resolve all reported errors before proceeding.
3. **Coverage** — if a coverage command exists, run it and confirm 100% coverage for changed code paths.

If both fixers and checkers fail or are not found, stop and inform the user.
