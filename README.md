<p align="center">
  <img src="assets/logo.png" alt="Cursor rules" width="280">
</p>

# Cursor Rules for PHP and Laravel

**Cursor rules for PHP and Laravel** — a complete set of `.mdc` rule files and Cursor Agent skills for the Cursor editor. One package for PHP and Laravel cursor rules: coding standards, testing, and conventions. The installer discovers the project root (via `composer.json` lookup from the current directory), mirrors the `rules/` directory into `.cursor/rules` and the `skills/` directory into `.cursor/skills`, and copies or symlinks every file into the target project. Use cursor rules for PHP and Laravel to keep every edit aligned with enforced standards, plus comprehensive Agent skills for issue resolution, bug fixing, code review, security analysis, refactoring, testing, and package review.

## Why This Package

- cursor rules for PHP and Laravel in one Composer-installable package
- unified PHP coding guidelines for PHP 8.4 projects
- Pest-based testing with mandatory code analysis and 100% coverage
- strong focus on clean code: typed properties, SRP, no redundant comments
- **28 comprehensive Agent skills** for automated workflows (v0.5)
- fast onboarding inside development repositories

## Installation

```bash
composer require pekral/cursor-rules --dev
vendor/bin/cursor-rules install
```

By default the installer targets **Cursor** only (`.cursor/rules`, `.cursor/skills`). Use `--editor=` to choose the agent:

- **cursor** (default): `.cursor/rules`, `.cursor/skills`
- **claude**: `.claude/rules`, `.claude/skills`, and when `HOME`/`USERPROFILE` is set also `~/.claude/skills`
- **codex**: `.codex/rules`, `.codex/skills`, and when `HOME`/`USERPROFILE` is set also `~/.codex/skills`
- **all**: all of the above (Cursor, Claude, Codex in project + home)

When the package is required via Composer, sources are read from `vendor/pekral/cursor-rules/rules` and `vendor/pekral/cursor-rules/skills`; in development it falls back to the local `rules/` and `skills/` directories.

**Important:** By default, the installer only copies missing files and keeps existing content untouched. Use the `--force` flag to overwrite existing files: `vendor/bin/cursor-rules install --force`. This is particularly useful when you want to update rules to their latest versions or when you've made local changes that should be replaced. The file `.cursor/rules/project.mdc` is never overwritten once it exists in the target project, so you can safely customize it for your project.

### Installing rules from GitHub (Cursor only)

You can use this repository as a **Remote Rule** in Cursor without installing the package via Composer:

1. Open **Cursor Settings** → **Rules**.
2. In the **Project Rules** section, click **Add Rule**.
3. Select **Remote Rule (Github)**.
4. Enter the repository URL: `https://github.com/pekral/cursor-rules`.

Cursor will fetch and apply the rules from the repository. Note: this method provides rules only; Agent skills are installed into your project when you use the Composer-based installation above.

### Available Commands

```bash
vendor/bin/cursor-rules help                        # print help
vendor/bin/cursor-rules install                     # install for Cursor (default)
vendor/bin/cursor-rules install --editor=claude     # install for Claude
vendor/bin/cursor-rules install --editor=codex      # install for Codex
vendor/bin/cursor-rules install --editor=all        # install for Cursor, Claude, and Codex
vendor/bin/cursor-rules install --force             # overwrite existing files
vendor/bin/cursor-rules install --symlink          # prefer symlinks (fallback to copy)
```

### Installer Flow

1. Determine the project root by walking up from the current directory until `composer.json` is found.
2. Resolve the rules source (local `rules/` or `vendor/pekral/cursor-rules/rules`).
3. Install rules into the target directory(ies) for the chosen editor (see `--editor`).
4. If present, resolve the skills source and install into the corresponding skill directory(ies).
5. Optionally overwrite existing files with `--force`; use `--symlink` to prefer symlinks (fallback to copy on Windows).
6. Surface explicit errors for missing directories, removal failures, and copy/symlink failures.

### CLI Switches

| Option            | Description                                                                 |
|-------------------|-----------------------------------------------------------------------------|
| `--editor=EDITOR` | Target editor: `cursor` (default), `claude`, `codex`, `all`.               |
| `--force`         | Overwrite files that already exist in the target directory.                |
| `--symlink`       | Create symlinks when the OS permits; automatically falls back to copy.     |
| *(default)*       | Only copy missing files and keep existing content untouched.              |

---

# 🎯 Skills Overview — **NEW in v0.5**

> **Major Update:** The skills system has been significantly expanded in version 0.5, providing comprehensive automation for common development workflows including issue resolution, code review, security analysis, and testing.

Agent skills are installed into the chosen editor’s skill directory (see `--editor`). Use `--editor=all` to install for Cursor, Claude, and Codex at once. They can be invoked when relevant. Each skill follows project conventions, ensures code quality, and maintains 100% test coverage where applicable.

## Issue Resolution & Bug Fixing

| Skill                    | Description                                                                 |
|--------------------------|-----------------------------------------------------------------------------|
| `resolve-jira-issue`     | End-to-end JIRA issue resolution: analyze, fix bugs, refactor code, perform code and security reviews, ensure 100% test coverage, run CI checks, and create pull requests. Links PRs to JIRA and updates issue status. |
| `resolve-random-jira-issue` | Resolves random JIRA issues: fix bugs, refactor, code and security review, 100% test coverage, CI checks, create PRs. Links PRs to JIRA and updates issue status. |
| `resolve-github-issue`   | Resolves GitHub issues by fixing bugs, refactoring code, performing code and security reviews, ensuring 100% test coverage, running CI checks, and creating pull requests. Updates GitHub issues with review results. |
| `resolve-bugsnag-issue`  | Resolve Bugsnag issues by fixing bugs, refactoring code, performing code and security reviews, ensuring 100% test coverage, running CI checks, and creating pull requests. Updates GitHub issues with review results. |
| `merge-github-pr`        | Merge PRs when they are ready for deployment, one by one. |
| `merge-github-prs`       | Merge multiple PRs in sequence only when CI is successful and there are no merge conflicts. |
| `analyze-problem`        | Analyze problems from issue trackers. Downloads and reviews attachments, provides technical analysis and solutions, and creates human-readable explanations for both technical and non-technical audiences. |

## Code Review & Quality

| Skill                    | Description                                                                 |
|--------------------------|-----------------------------------------------------------------------------|
| `code-review`            | Senior PHP code reviewer. Use when reviewing pull requests, examining code changes vs master branch, or when the user asks for a code review. Read-only review — never modifies code. |
| `process-code-review`    | Processes feedback from existing pull request reviews: finds latest PR for task, resolves review comments, updates review status comments, and triggers the next review cycle. |
| `code-review-github`     | Performs comprehensive code review for GitHub pull requests. Analyzes code changes, identifies critical and moderate issues, runs tests, and posts review comments. Reviews code quality, security, and adherence to project standards. |
| `code-review-jira`       | Performs code review for JIRA issues. Analyzes pull requests, identifies critical and moderate issues, runs tests, and posts review comments to GitHub PRs. Reviews code quality, security, and adherence to project standards. |
| `security-review`        | Performs comprehensive security review following OWASP Top 10 and security best practices. Checks for injection vulnerabilities, authentication flaws, sensitive data exposure, misconfigurations, and provides structured security reports with severity levels. |
| `class-refactoring`      | Refactors PHP classes following Laravel best practices and SOLID principles. Ensures code quality, maintains functionality, improves testability, and achieves 100% code coverage. Focuses on single responsibility, DRY principle, and clean code structure. |
| `smartest-project-addition` | Proposes exactly one highest-value and radically useful project addition, including impact, risk, and minimal rollout plan. |
| `understand-propose-implement-verify` | Enforces a strict four-step workflow: understand the problem, propose solution, implement, and verify correctness. |

## Testing, Package & SEO

| Skill                    | Description                                                                 |
|--------------------------|-----------------------------------------------------------------------------|
| `postman-collections`    | Generates or updates Postman collections when API endpoints are created or changed, keeps examples and auth variables in sync, and validates collection importability. |
| `create-test`            | Creates tests following project conventions and patterns. Ensures deterministic tests, 100% code coverage for changes, uses data providers where appropriate, and mocks only external services or exception scenarios. |
| `rewrite-tests-pest`     | Rewrites existing tests to PEST syntax following project conventions. Ensures DRY principles, uses data providers, maintains 100% coverage, and verifies test functionality. |
| `package-review`         | Reviews composer.json packages by validating structure, checking required fields, verifying links, and ensuring proper configuration of autoloading, dependencies, and metadata. |
| `seo-fix`                | Maintains and extends SEO setup (robots.txt, sitemap.xml, meta tags). Use when adding or changing public routes, disallow rules, sitemap entries, canonical/robots/OG tags, or when the user asks about SEO, sitemap, or robots. |
| `seo-geo`                | SEO plus GEO (AI search citation optimization): audits, keywords, structured data strategy, content patterns, platform notes; pairs with `seo-fix` for Laravel implementation. |

---

## Rules Overview

Cursor rules for PHP and Laravel included in this package:

| File                   | Description                                                | Scope    |
|------------------------|------------------------------------------------------------|----------|
| `project.mdc`          | Base rules for actual project                              | Always   |
| `php/core.mdc`         | Project tech stack and core context                        | Always   |
| `php/standards.mdc`    | Unified coding standards for PHP/Laravel projects          | Always   |
| `git/conventions.mdc`  | Git and commit conventions                                 | Always   |
| `git/general.mdc`      | Git workflow — analyze branch/commits when outside main    | Always   |
| `git/pr.mdc`           | Create pull request in Github                              | Always   |
| `laravel/architecture.mdc` | Laravel architecture and conventions                   | Laravel  |
| `laravel/filament.mdc` | Filament v4 specific rules                                 | Filament |
| `laravel/livewire.mdc` | Livewire component rules and conventions                   | Livewire |
| `sql/optimalize.mdc`   | SQL query optimization, index design, schema standards     | Always   |
| `security/backend.md`  | Backend security rules and OWASP Top 10 checks             | Always   |
| `security/frontend.md`  | Frontend security rules (XSS, CSRF, CSP)                  | Frontend |
| `security/mobile.md`   | Mobile-specific security rules and WebView checks          | Mobile   |

All `.mdc` and `.md` files are ready for automatic injection by Cursor so every PHP and Laravel edit stays aligned with the enforced standards.

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
