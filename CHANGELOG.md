# Changelog

All notable changes to `cursor-rules` will be documented in this file.

## [Unreleased]

- 🐛 **Fixed**: CR skills now systematically review all open PRs per issue instead of only one (#227)
- 🐛 **Fixed**: JIRA skills now consistently prefer `acli` console tool via shared `jira-operations.mdc` rule (#228)
- 🐛 **Fixed**: CR no longer flags custom named static constructors (`fromModel()`, `fromRequest()`) on Spatie DTOs as issues (#230)
- 🐛 **Fixed**: CR skills now include mandatory regression analysis to verify changes don't break existing functionality outside ticket scope (#233)
- 🐛 **Fixed**: `code-review` skill now enforces English-only output — Czech Deliver/Communication sections translated (#235)
- 🔧 **Changed**: move shared rule files from `rules/skills/` to `rules/`, update all skill references (#238)
- 🐛 **Fixed**: `create-missing-tests-in-pr` and `test-like-human` skills now use shared `github-operations.mdc` / `jira-operations.mdc` rules instead of inline preferences (#237)

## [0.6.2] - 2026-04-07

- 📝 **Changed**: consolidate CHANGELOG (single [Unreleased] section, remove duplicate blocks)
- 🔧 **Changed**: refresh readme and align Rules Overview with actual rule files
- 🔧 **Changed**: cleanup rules and skills
- 🔧 **Changed**: composer update dependencies
- 🐛 **Fixed**: installer (project root, rules/skills paths)
- 🐛 **Fixed**: install rules to correct folder
- 🐛 **Fixed**: Symfony console v8 compatibility
- ♻️ **Refactored**: optimize commands and prompts
- ✨ **Added**: new Agent skills (resolve-github-issue, merge-github-pr, code-review, seo-fix, package-review, and others)
- ✨ **Added**: new Agent skill `process-code-review` for handling pull request review feedback loops
- 🗑️ **Removed**: obsolete Agent skills (`class-refactoring-plan`, `composer-install`, `interactive-testing`, `merge-github-prs`, `resolve-random-github-issue`) and `skills/ANALYZA-SKILLS.md`
- ✨ **Added**: new Agent skill `smartest-project-addition` for single high-impact innovation prompt outcomes
- ✨ **Added**: new Agent skill `understand-propose-implement-verify` for strict problem-solving workflow
- 📝 **Changed**: Update SKILL.md files and rule files (php standards, sql, git, laravel)
- 📝 **Changed**: Laravel testing rules and test-writing skills require Eloquent rows in tests to be created only via model factories (#147)
- 📝 **Changed**: Laravel rules and test-writing skills clarify database schema defaults as source of truth—avoid duplicating them in PHP/factories (#152)
- 📝 **Changed**: Laravel testing rules and test-writing skills require queueing jobs in tests via `JobClass::dispatch()` only (#153)
- ✨ **Added**: new Agent skill `seo-geo` for SEO and generative-engine optimization strategy (#164)
- ✨ **Added**: new Agent skill `create-jira-issue-from-pr` for creating JIRA-ready issue drafts from GitHub PR review context while preserving original task text (#205)
- ✨ **Added**: new Agent skill `laravel-telescope` for Telescope URL analysis, DB request correlation, and optimization recommendations (#211)
