# Step Decomposition Guidelines

When splitting an assignment into steps, follow these rules:

## Core Principles

- Each step must be independently deliverable and testable.
- Order steps by dependency — earlier steps should not depend on later ones.
- Group related changes together (e.g., migration + model + factory = one step).
- Separate infrastructure/setup steps from business logic steps.
- Separate frontend and backend work when they can be developed in parallel.
- Keep each step small enough to be reviewed in a single PR.
- If a step is too large, split it further.

## Granularity Checklist

A step is the right size when:
- [ ] It can be described in a single sentence
- [ ] It produces a single, reviewable PR
- [ ] It can be tested independently
- [ ] It does not mix unrelated concerns (e.g., DB migration and UI layout)

A step is too large when:
- [ ] It touches more than 3 unrelated areas of the codebase
- [ ] It cannot be reviewed in under 30 minutes
- [ ] It contains multiple independently testable features

## Ordering Rules

1. Database/schema changes come first
2. Backend models and services come next
3. API endpoints follow the services they depend on
4. Frontend work comes after the API it consumes
5. Integration and end-to-end concerns come last
