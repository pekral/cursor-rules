# Regression Analysis

## Purpose

Ensure that changes in the PR do not break existing functionality outside the ticket scope.

## Procedure

For every changed file:

1. **Trace callers and dependents** — Identify all code that calls or depends on the changed methods, classes, or interfaces.
2. **Check shared logic** — If the change modifies helpers, services, traits, base classes, or interfaces, verify that all consumers still behave correctly.
3. **Evaluate side effects** — Determine whether the modification could alter return types, exception behavior, event dispatching, or data formats relied upon elsewhere.
4. **Flag regression risk** — Even if the new code is correct in isolation, breaking unrelated features is **Critical**.

## What to look for

- Changed method signatures (added/removed/reordered parameters)
- Altered return values or types
- Modified shared constants or configuration
- Changed database schema used by other modules
- Removed or renamed public APIs
- Modified event names or payloads

## Reporting

Every regression risk must be reported as a finding. If the risk is uncertain but plausible, include it with a note explaining the uncertainty.
