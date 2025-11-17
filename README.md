# Cursor Rules

[![Code Quality Check](https://github.com/pekral/cursor-rules/workflows/Code%20Quality%20Check/badge.svg)](https://github.com/pekral/cursor-rules/actions)
[![Tests](https://github.com/pekral/cursor-rules/workflows/Tests/badge.svg)](https://github.com/pekral/cursor-rules/actions)

Cursor Rules ships a complete set of `.mdc` files for the Cursor editor. The installer discovers the project root, mirrors the entire `rules/` directory (including empty subfolders), and copies or symlinks every file into the target project. The result is a consistent bundle of rules for PHP, Laravel, and testing workflows.

## Why This Package

- unified coding guidelines for PHP 8.4 projects
- Pest-based testing with mandatory code analysis and 100 % coverage
- strong focus on clean code: typed properties, SRP, no redundant comments
- fast onboarding inside development repositories

## Installation

```bash
composer require pekral/cursor-rules --dev
vendor/bin/cursor-rules install
```

The installer creates `.cursor/rules` in the project root by default. When the package is required via Composer, the source files are read from `vendor/pekral/cursor-rules/rules`; in development it falls back to the local `rules/` directory.

### Available Commands

```bash
vendor/bin/cursor-rules help               # print help
vendor/bin/cursor-rules install            # copy/symlink everything to the target
vendor/bin/cursor-rules install --force    # overwrite existing files
vendor/bin/cursor-rules install --symlink  # prefer symlinks (fallback to copy)
```

### Installer Flow

1. Determine the project root (`composer.json` search or configured fallback).
2. Resolve the rules source (local `rules/` or `vendor/...`).
3. Resolve the destination: `.cursor/rules` or `CURSOR_RULES_TARGET_DIR`.
4. Create the destination root and replicate the whole directory tree, including empty folders.
5. Copy or symlink every file, optionally overriding existing files via `--force`.
6. Surface explicit errors for missing directories, removal failures, copy/symlink issues, etc.

### CLI Switches

| Option       | Description                                                             |
|--------------|-------------------------------------------------------------------------|
| `--force`    | Overwrite files that already exist in the target directory.             |
| `--symlink`  | Create symlinks when the OS permits; automatically falls back to copy.  |
| *(default)*  | Only copy missing files and keep existing content untouched.            |

### Environment Variables

| Variable                           | Purpose                                                               |
|------------------------------------|------------------------------------------------------------------------|
| `CURSOR_RULES_TARGET_DIR`          | Absolute path to the destination (for example `/app/.cursor/rules`).  |
| `CURSOR_RULES_PROJECT_ROOT`        | Skips auto-discovery of the project root.                             |
| `CURSOR_RULES_PROJECT_ROOT_FALLBACK` | Fallback root if `getcwd()` cannot be resolved.                      |
| `CURSOR_RULES_DISABLE_SYMLINKS`    | Forces copy mode even when `--symlink` is used.                       |
| `CURSOR_RULES_FORCE_WINDOWS`       | Simulates a Windows environment (symlinks are disabled).              |
| `CURSOR_RULES_FAIL_SYMLINK`        | Test flag that forces a symlink failure.                              |
| `CURSOR_RULES_FAIL_COPY`           | Test flag that forces a copy failure.                                 |

## Rules Overview

| File                 | Description                                                                  | Scope  |
|----------------------|------------------------------------------------------------------------------|--------|
| `php-classes.mdc`    | Class structure, typed properties, SRP                                       | Always |
| `php-docs.mdc`       | Required docs for iterables, generics, array shapes                          | Always |
| `php-fixing-bugs.mdc`| Bug-fix workflow, tests-first refactoring guardrails                        | Always |
| `php-laravel.mdc`    | Laravel conventions (naming, configs, routes, Blade, jobs, events)           | Always |
| `php-standards.mdc`  | Global clean-code rules, no redundant comments                               | Always |
| `php-testing.mdc`    | Pest conventions, 100 % coverage, no private/protected assertions             | Always |
| `templates/*.md`     | Task templates and checklists (e.g., class refactoring)                      | Manual |

All `.mdc` files are ready for automatic injection by Cursor so every edit stays aligned with the enforced standards.

## Development & Testing

- Run tests: `./vendor/bin/pest`
- Full quality check: `composer check`
- Generate 100 % coverage report: `composer test:coverage`
- Remove `coverage.xml` before committing if it was produced locally

## License

MIT — free to use, modify, and distribute.
