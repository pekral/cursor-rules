# Changelog

All notable changes to `cursor-rules` will be documented in this file.

## [Unreleased]

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
- ✨ **Added**: new Agent skill `merge-github-prs` for batch-merging PRs with successful checks and no conflicts
- ✨ **Added**: new Agent skill `smartest-project-addition` for single high-impact innovation prompt outcomes
- ✨ **Added**: new Agent skill `understand-propose-implement-verify` for strict problem-solving workflow
- 📝 **Changed**: Update SKILL.md files and rule files (php standards, sql, git, laravel)
- 📝 **Changed**: Laravel testing rules and test-writing skills require Eloquent rows in tests to be created only via model factories (#147)
- 📝 **Changed**: Laravel rules and test-writing skills clarify database schema defaults as source of truth—avoid duplicating them in PHP/factories (#152)
- 📝 **Changed**: Laravel testing rules and test-writing skills require queueing jobs in tests via `JobClass::dispatch()` only (#153)
