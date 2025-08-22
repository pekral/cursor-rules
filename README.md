# Cursor Rules

[![Code Quality Check](https://github.com/pekral/cursor-rules/workflows/Code%20Quality%20Check/badge.svg)](https://github.com/pekral/cursor-rules/actions)
[![Tests](https://github.com/pekral/cursor-rules/workflows/Tests/badge.svg)](https://github.com/pekral/cursor-rules/actions)
[![Auto Fix Code](https://github.com/pekral/cursor-rules/workflows/Auto%20Fix%20Code/badge.svg)](https://github.com/pekral/cursor-rules/actions)

This repository contains **custom rules for the Cursor editor**.  
The goal is to provide consistent, automated, and high-quality coding standards across PHP, Laravel, and testing workflows.

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
| **spatie.mdc**      | **Laravel & PHP coding standards** derived from Spatie’s guidelines. Follow Laravel conventions first, then PSR standards. | Always apply |
| **git.mdc**         | Enforce **Conventional Commits**. All commit messages must follow the specification with short, consistent messages. | Always Apply |
| **code-aquality.mdc** | **Code Quality guidelines**: target the actual PHP version, verify information, avoid assumptions, no apologies, avoid commented-out code, and prefer file-by-file changes. | Always Apply |
| **clean-code.mdc**  | **Clean Code rules**: typed properties, constructor promotion, short nullable notation, explicit `void` return types, and consistent class structure. | Always Apply |

---

## 🚀 How to Use

1. Copy or symlink the `.cursor/rules/` folder into your project.  
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

## 🧪 Testing

This project includes comprehensive testing with PHPUnit. Run the test suite:

```bash
./vendor/bin/phpunit
```

### Composer Scripts

The project includes several composer scripts for development:

```bash
# Run code quality checks
composer run check

# Auto-fix code style and quality issues
composer run fix

# Run tests
composer run test
```

### GitHub Actions

This project uses GitHub Actions for continuous integration. Workflows are automatically triggered on:

- **Push** to main/master/develop branches
- **Pull requests** to main/master/develop branches  
- **Tags** (for releases)
- **Scheduled** auto-fixes (every Sunday)

#### Available Workflows

- 🎯 **Code Quality Check** - PHPCS, PHPStan, Pint checks
- 🧪 **Tests** - PHPUnit testing (PHP 8.3, 8.4)
- 🔧 **Auto Fix Code** - Automatic code improvements
- 🚀 **Release** - Pre-release checks and release creation

#### Code Quality Tools

- **PHP CodeSniffer** - Code style enforcement
- **PHPStan** - Static analysis
- **Laravel Pint** - Code formatting
- **Rector** - Code improvements

See [`.github/README.md`](.github/README.md) for detailed workflow documentation.
