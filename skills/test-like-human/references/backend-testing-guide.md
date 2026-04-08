# Backend Testing Guide

## When to Use Backend Code Execution

Use when the change is primarily **backend logic** (models, services, actions, jobs, commands, or data transformations) that cannot be fully validated through the UI or an API endpoint alone.

Specific triggers:

- The changed code is not directly triggered by a user action in the browser.
- The change affects data processing, business rules, or database state not visibly reflected in the UI.
- A senior tester in a dev team would normally ask a developer to "run it in tinker" to confirm the result.

## Execution Steps

1. Identify the entry point of the changed code (action class, model method, service, command, etc.) from the PR diff or description.
2. Use `php artisan tinker` (or an equivalent CLI client) to set up the scenario:
   - Create or load the required model instances / test data (for Eloquent, prefer `Model::factory()`)
   - Invoke the changed class or method directly
   - Inspect the return value and the resulting database state
3. Verify that:
   - The output matches the expected behavior described in the PR
   - Database records are created, updated, or deleted as intended
   - No unexpected side effects occur (e.g., duplicate records, wrong values, exceptions)
4. Translate the technical result into a **human-readable conclusion** — focus on what changed from the user's perspective, not on the implementation details.

## Rules

- Run only the minimum commands needed to validate the scenario.
- Never modify production data; use test/seed data or a local development environment.
- Do not expose raw tinker output in the final report — summarise the finding in plain language.
- If tinker is not available, use the project's equivalent (Node.js REPL, Rails console, Django shell, etc.).
