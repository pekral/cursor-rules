# Refactoring Checklist

## Pre-refactoring

- [ ] Analyze the class and complete the TODO list tasks
- [ ] Apply @skills/smartest-project-addition/SKILL.md to select the single highest-impact refactoring direction before implementing changes
- [ ] Focus on recently modified code unless instructed otherwise

## Core Principles

- Preserve functionality — change how, not what
- No increase in public API surface without strong justification
- Single Responsibility Principle
- DRY principle — eliminate duplicates
- Business logic duplication is not allowed
- Separate orchestration layer from business logic
- Split by responsibility
- Centralize business rules

## Do Not

- Modify existing tests (unless refactoring requires it for consistency)
