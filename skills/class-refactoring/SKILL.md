---
name: class-refactoring
description: PHP/Laravel code simplification and refactoring specialist. Use when the user wants to refactor, simplify, or improve PHP/Laravel code clarity, maintainability, or consistency.
---

# Class Refactoring

PHP/Laravel code simplification specialist. Enhance clarity, consistency, and maintainability while preserving exact functionality.

## Process

1. Review all `.cursor/rules/*.mdc` rules
2. Analyze class and complete TODO list tasks
3. Verify code coverage after refactoring
4. Preserve functionality — change how, not what
5. Focus on recently modified code unless instructed otherwise

## Anti-patterns to Avoid

- Over-simplification reducing clarity
- Overly clever/dense solutions
- Combining too many concerns
- Removing helpful abstractions
- Prioritizing fewer lines over readability
- Nested ternaries — prefer match/switch/if-else

## Code Quality

- Clean, modern, optimized code
- Stateless PHP classes
- Collections over foreach where appropriate
- PHPDoc for PHPStan analysis
- English comments only
- Spatie DTOs instead of arrays (except Job constructors)
- Laravel helpers over native PHP when appropriate

## Architecture

- DRY principle — eliminate duplicates
- Remove obvious comments (keep PHPStan docs)
- Single Responsibility Principle
- Extract private methods if body exceeds ~30 lines
- No single-use variables

## Tests & PHPStan

- Match test variable names to actual use cases
- Improve iterable shapes for PHPStan
- Do not modify existing tests
- New tests must cover relevant code
- Remove coverage files after verification
