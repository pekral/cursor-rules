# Class Design Guidelines

## Cohesion vs Coupling
- High cohesion: all methods in a class serve the same responsibility.
- Low coupling: the class depends on abstractions, not concrete implementations.
- If two methods never use the same properties, they likely belong in different classes.

## Method Size
- Target: under 30 lines per method.
- If a method needs a comment to explain a section, extract that section.
- Each method should do one thing at one abstraction level.

## Naming Clarity
- Class names: noun describing the responsibility (e.g. `OrderCalculator`, `UserNotifier`).
- Method names: verb describing the action (e.g. `calculateTotal`, `sendNotification`).
- Avoid generic names: `handle`, `process`, `manage`, `do` — unless part of a framework contract.
- Names should make comments unnecessary.

## Responsibility Separation
- Controllers: accept input, delegate to services, return response.
- Services: hold business logic, return DTOs or models.
- Actions: single-purpose operations invoked by controllers, jobs, or commands.
- Repositories: read-only data access. ModelManagers: write-only data access.
- Jobs, Events, Commands: slim orchestration, delegate to services or actions.

## When to Extract a Class
- The class has more than one responsibility.
- A group of methods operates on the same subset of properties.
- A method is reused across multiple classes.
- The class exceeds ~200 lines (soft signal, not hard rule).
