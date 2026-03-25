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
- 📝 **Changed**: Update SKILL.md files and rule files (php standards, sql, git, laravel)
