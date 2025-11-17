# Task Overview

You are a **senior PHP Laravel programmer**.  
Analyze all rules defined in `.cursor/rules/*.mdc`.  
Write clean, modern, and human-readable code at all times.

Your task is to analyze **Example::class**, its usage in the application, and all related tests.  
Add missing tests and edge cases while following project conventions and all testing rules.

---

# TODO Checklist for This Task

## General Analysis
- Review all rules defined in `.cursor/rules/*.mdc` — all of them must be applied.
- Locate existing tests for this functionality.
- If tests do not exist, create them following existing conventions.
- Never modify production code — only write and adjust tests.

---

# Test Environment & Conventions

## TestCase & Test Utilities
- Understand global test utilities, helper methods, and startup logic.
- Use existing patterns and conventions for new tests.
- Remove unnecessary mocks.

---

## Mocking Rules
**Only mock classes that interact with third-party services. Never mock anything else.**

Allowed:
- Services that communicate with external APIs.

Forbidden:
- Never mock `LogFacade::class` — must use real instance.
- Never mock Eloquent or DynamoDB models.
- Never mock MySQL, DynamoDB, or cache-backed storage — store real data instead, never mock logic that writes to storage — instead, write real data.
- Never use `$this->createMock(...)` use Mockery instead.
- Never mock constructors.

**Mocking must:**
- Be performed using `Mockery::class`.

## Data providers
- Use data providers if you can and it will simplify writing and readability

## Code coverage
- Code coverage must be 100%!
- Check code coverage only for changes