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
- **30 comprehensive Agent skills** for automated workflows (v0.6.1)
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

# 🎯 Skills Overview — **v0.6.1**

> Current release includes 31 skills for issue resolution, code review, refactoring, testing, security, SQL performance, and delivery workflows.

Agent skills are installed into the chosen editor’s skill directory (see `--editor`). Use `--editor=all` to install for Cursor, Claude, and Codex at once. They can be invoked when relevant. Each skill follows project conventions, ensures code quality, and maintains 100% test coverage where applicable.

> **Note:** Skill files internally reference `.claude/` paths (e.g. `@.claude/rules/…`, `@.claude/skills/…`). Use `--editor=claude` or `--editor=all` for full compatibility.

## Issue Resolution & Delivery

| Skill | Description |
|---|---|
| `analyze-problem` | Analyze problems from issue trackers, including attachments, technical context, and human-readable outputs. |
| `resolve-bugsnag-issue` | End-to-end Bugsnag issue resolution with fixes, review loops, coverage checks, and PR creation. |
| `resolve-github-issue` | End-to-end GitHub issue resolution with fixes, review loops, coverage checks, and PR creation. |
| `resolve-jira-issue` | End-to-end JIRA issue resolution with fixes, review loops, coverage checks, and PR creation. |
| `resolve-random-jira-issue` | Pick and resolve a random JIRA issue with the full quality workflow. |
| `answer-pr-questions` | Find unanswered current issue/PR questions and generate short PM/client-friendly unified answers. |
| `merge-github-pr` | Merge one GitHub PR that is ready for deployment. |
| `create-issue` | Create a tracker issue from provided task text while preserving original meaning and structure. |
| `create-jira-issue-from-pr` | Draft a JIRA-ready issue from GitHub PR review context while preserving original assignment text. |

## Code Review, Security & Architecture

| Skill | Description |
|---|---|
| `code-review` | Senior PHP code review focused on architecture, risk, and behavior (read-only). |
| `code-review-github` | Review GitHub pull requests with severity-based findings and review comments. |
| `code-review-jira` | Review JIRA-linked changes with GitHub PR comments and structured findings. |
| `process-code-review` | Process existing review feedback, resolve comments, and prepare next review cycle. |
| `security-review` | OWASP-focused security review (injection, auth, exposure, misconfigurations). |
| `class-refactoring` | Refactor PHP classes using SOLID and Laravel best practices with testability focus. |
| `race-condition-review` | Review shared-state and concurrency paths for race conditions and atomicity gaps. |
| `refactor-entry-point-to-action` | Refactor controller/job/command/listener entry-point logic into Action classes. |
| `smartest-project-addition` | Propose one high-impact, low-risk project improvement. |
| `understand-propose-implement-verify` | Enforce a strict 4-step loop: understand, propose, implement, verify. |

## Testing & Quality Automation

| Skill | Description |
|---|---|
| `create-test` | Add tests following project rules with deterministic behavior and high coverage. |
| `create-missing-tests-in-pr` | Validate review recommendations and add missing tests for current PR changes. |
| `rewrite-tests-pest` | Rewrite tests to PEST style while preserving behavior and conventions. |
| `test-like-human` | Validate PR behavior from user perspective using scenario-driven testing. |
| `test-driven-development` | Enforce strict red-green-refactor flow for bugfixes and features. |
| `postman-collections` | Create or update Postman collections for changed API endpoints. |

## Platform, Data & SEO

| Skill | Description |
|---|---|
| `composer-update` | Analyze composer updates, conflicts, and changelog impact. |
| `package-review` | Review `composer.json` and package metadata/configuration quality. |
| `mysql-problem-solver` | Diagnose and optimize MySQL queries, indexes, and execution plans. |
| `laravel-telescope` | Analyze Laravel Telescope request data from URL, match entries in DB, and propose concrete optimizations. |
| `seo-fix` | Implement and maintain Laravel SEO assets (robots/sitemap/meta/canonical). |
| `seo-geo` | Improve SEO + GEO (AI search visibility and citation-readiness). |

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
| `laravel/arch-app-services.mdc` | BaseModelService/Data Validator conventions for `pekral/arch-app-services` projects | Laravel |
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
