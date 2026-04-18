---
name: understand-propose-implement-verify
description: "Use when following a strict problem-solving loop: understand, propose, implement, verify."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Always follow this order: understand → propose → implement → verify
- Prefer existing project skills over custom solutions; do not duplicate logic already covered by a skill

## Use when
- Solving a task that requires structured thinking and controlled execution
- Coordinating multiple steps or skills

## Execution

### 1. Understand
- Analyze the problem, context, and related resources
- Classify the task (bug, feature, refactor, review, etc.)
- Define a short checklist of goals, constraints, and assumptions

### 2. Propose
- Suggest the smallest safe solution
- Explain why this approach is preferred (impact, risk, trade-offs)
- Select relevant existing skills to execute the plan

### 3. Implement
- Execute the solution using selected skills where applicable
- Keep changes focused and aligned with project conventions
- Add or update tests for changed behavior

### 4. Verify
- Validate the result against requirements
- Run relevant checks/tests for the affected scope
- Ensure no regressions or unintended side effects
- Summarize what was changed, tested, and any remaining risks

## Done when
- The task is fully addressed
- The solution follows the defined loop
- Existing skills were reused where applicable
- Results are validated and clearly summarized
