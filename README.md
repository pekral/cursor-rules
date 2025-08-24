# Cursor Rules

[![Code Quality Check](https://github.com/pekral/cursor-rules/workflows/Code%20Quality%20Check/badge.svg)](https://github.com/pekral/cursor-rules/actions)
[![Tests](https://github.com/pekral/cursor-rules/workflows/Tests/badge.svg)](https://github.com/pekral/cursor-rules/actions)
[![Auto Fix Code](https://github.com/pekral/cursor-rules/workflows/Auto%20Fix%20Code/badge.svg)](https://github.com/pekral/cursor-rules/actions)

This repository contains **custom rules for the Cursor editor**.  
The goal is to provide consistent, automated, and high-quality coding standards across PHP, Laravel, and testing workflows.

## 🚀 Installation

### Quick Install

Install cursor rules using the provided binary script:

```bash
# Install via Composer
composer require pekral/cursor-rules --dev

# Install rules to your project
vendor/bin/cursor-rules install
```

### Installation Options

```bash
# Force overwrite existing files
vendor/bin/cursor-rules install --force

# Create symlinks instead of copying (recommended for development)
vendor/bin/cursor-rules install --symlink

# Show help
vendor/bin/cursor-rules help
```

The installer will automatically:
- Create `.cursor/rules/` directory in your project root
- Copy or symlink all rule files
- Handle both development and production installations
- Preserve existing rules (unless `--force` is used)

## 📂 Repository Structure

```
.cursor/
 └─ rules/
     ├─ testing.mdc
     ├─ spatie.mdc
     ├─ git.mdc
     ├─ code-aquality.mdc
     └─ clean-code.mdc
```

Each rule file (`.mdc`) includes instructions that Cursor automatically attaches when writing, editing, or generating code.

---

## 📖 Rules Overview

| Rule File           | Description                                                                                         | Scope / Type       |
|---------------------|-----------------------------------------------------------------------------------------------------|--------------------|
| **testing.mdc**     | Guidelines for writing and maintaining **Pest tests**. Analyze classes before writing tests, keep tests simple and readable, and follow existing patterns. | Always Apply |
| **spatie.mdc**      | **Laravel & PHP coding standards** derived from Spatie's guidelines. Follow Laravel conventions first, then PSR standards. | Always apply |
| **git.mdc**         | Enforce **Conventional Commits**. All commit messages must follow the specification with short, consistent messages. | Always Apply |
| **code-aquality.mdc** | **Code Quality guidelines**: target the actual PHP version, verify information, avoid assumptions, no apologies, avoid commented-out code, and prefer file-by-file changes. | Always Apply |
| **clean-code.mdc**  | **Clean Code rules**: typed properties, constructor promotion, short nullable notation, explicit `void` return types, and consistent class structure. | Always Apply |

---

## 🎯 How to Use

1. **Install rules** using `vendor/bin/cursor-rules install`
2. Cursor automatically applies rules marked with `alwaysApply: true`.  
3. To invoke **manual rules**, call them in the Cursor chat using `@rule-name`.  
4. When editing code, these rules act as **guardrails** to enforce standards, improve readability, and ensure test coverage.

---

## ✨ Benefits

- Consistent **Laravel & PHP coding style** across projects.  
- High-quality, maintainable code with **Clean Code principles**.  
- Automated enforcement of **Conventional Commits**.  
- Smarter, **Pest-based test generation**.  
- Better readability and reduced cognitive complexity.

---

## 📝 License

MIT – free to use, modify, and distribute.

---

📌 This setup ensures that every piece of generated code, commit, and test in Cursor follows **best practices** while remaining clean, maintainable, and production-ready.

---

See [`.github/README.md`](.github/README.md) for detailed workflow documentation.
