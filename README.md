<p align="center">
  <img src="assets/logo.png" alt="Cursor rules" width="280">
</p>

# Cursor Rules for PHP and Laravel

**Cursor rules for PHP and Laravel** — a complete set of `.mdc` rule files and Cursor Agent skills for the Cursor editor. One package for PHP and Laravel cursor rules: coding standards, testing, and conventions. The installer discovers the project root (via `composer.json` lookup from the current directory), mirrors the `rules/` directory into `.cursor/rules`, the `skills/` directory into `.cursor/skills`, and the `agents/` directory into `.cursor/agents`, and copies or symlinks every file into the target project. Use cursor rules for PHP and Laravel to keep every edit aligned with enforced standards, plus optional Agent skills for bug fixing, code review, refactoring, testing, and package review.

## Why This Package

- cursor rules for PHP and Laravel in one Composer-installable package
- unified PHP coding guidelines for PHP 8.4 projects
- Pest-based testing with mandatory code analysis and 100% coverage
- strong focus on clean code: typed properties, SRP, no redundant comments
- fast onboarding inside development repositories

## Installation

```bash
composer require pekral/cursor-rules --dev
vendor/bin/cursor-rules install
```

The installer creates `.cursor/rules`, `.cursor/skills` and `.cursor/agents` in the project root. When the package is required via Composer, sources are read from `vendor/pekral/cursor-rules/rules`, `vendor/pekral/cursor-rules/skills` and `vendor/pekral/cursor-rules/agents`; in development it falls back to the local `rules/`, `skills/` and `agents/` directories.

### Available Commands

```bash
vendor/bin/cursor-rules help               # print help
vendor/bin/cursor-rules install            # copy/symlink everything to the target
vendor/bin/cursor-rules install --force    # overwrite existing files
vendor/bin/cursor-rules install --symlink  # prefer symlinks (fallback to copy)
```

### Installer Flow

1. Determine the project root by walking up from the current directory until `composer.json` is found.
2. Resolve the rules source (local `rules/` or `vendor/pekral/cursor-rules/rules`).
3. Create `.cursor/rules` and replicate the full directory tree; copy or symlink each file.
4. If present, resolve the skills source (local `skills/` or `vendor/pekral/cursor-rules/skills`) and install into `.cursor/skills` the same way.
5. If present, resolve the agents source (local `agents/` or `vendor/pekral/cursor-rules/agents`) and install into `.cursor/agents` the same way.
6. Optionally overwrite existing files with `--force`; use `--symlink` to prefer symlinks (fallback to copy on Windows).
7. Surface explicit errors for missing directories, removal failures, and copy/symlink failures.

### CLI Switches

| Option       | Description                                                             |
|--------------|-------------------------------------------------------------------------|
| `--force`    | Overwrite files that already exist in the target directory.             |
| `--symlink`  | Create symlinks when the OS permits; automatically falls back to copy.  |
| *(default)*  | Only copy missing files and keep existing content untouched.            |

## Rules Overview

Cursor rules for PHP and Laravel included in this package:

| File                | Description                                        | Scope   |
|---------------------|----------------------------------------------------|---------|
| `php-core.mdc`      | PHP project tech stack and core context            | Always  |
| `php-filament.mdc`  | Filament v4 rules (resources, enums, tests)        | Filament|
| `php-git.mdc`       | Git and commit conventions for PHP projects        | Always  |
| `php-laravel.mdc`   | Laravel and PHP architecture and conventions       | Laravel |
| `php-standards.mdc` | Unified PHP coding standards for Laravel projects  | Always  |

All `.mdc` files are ready for automatic injection by Cursor so every PHP and Laravel edit stays aligned with the enforced standards.

## Skills Overview

Agent skills are installed into `.cursor/skills/` and can be invoked when relevant:

| Skill             | Description                                                                 |
|-------------------|-----------------------------------------------------------------------------|
| `auto-fix-bug`    | Fix a reported bug end-to-end: reproduce, add test, fix, open PR.           |
| `class-refactoring` | Simplify and refactor PHP/Laravel code, improve clarity and consistency.  |
| `code-review`     | Senior PHP/Laravel code review (pull requests, changes vs base); read-only.|
| `package-review`  | Review and validate public packages (GitHub, composer.json, docs links).   |
| `test-create`     | Create or extend PHP/Laravel tests, follow project conventions, 100% coverage. |

## Development & Testing

### Composer Scripts

```bash
composer check              # run full quality check (normalize, phpcs, pint, rector, phpstan, audit, tests)
composer fix                # run all automatic fixes (normalize, rector, pint, phpcs)
composer build              # fix then check
composer analyse            # run PHPStan static analysis
composer test:coverage       # run tests with 100% coverage
composer coverage           # alias for test:coverage
composer security-audit     # run security audit of dependencies
```

### Individual Commands

```bash
composer phpcs-check        # PHP CodeSniffer check
composer phpcs-fix          # PHP CodeSniffer fix
composer pint-check         # Laravel Pint check
composer pint-fix           # Laravel Pint fix
composer rector-check       # Rector check (dry-run)
composer rector-fix         # Rector fix
```

### Testing

```bash
./vendor/bin/pest           # run all tests
composer test:coverage      # run tests with coverage (min. 100%)
```

Remove `coverage.xml` before committing if it was produced locally.

## Author

**Petr Král** — PHP Developer & Laravel programmer, open source contributor ([pekral.cz](https://pekral.cz)).

## License

MIT — free to use, modify, and distribute.
