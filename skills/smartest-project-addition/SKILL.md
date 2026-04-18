---
name: smartest-project-addition
description: "Use when you want exactly one high-impact, concrete proposal for the next project addition."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- If the current project uses Laravel, also apply `@rules/laravel/laravel.mdc`, `@rules/laravel/architecture.mdc`, `@rules/laravel/filament.mdc`, and `@rules/laravel/livewire.mdc`
- Recommend exactly one addition
- Do not include alternative proposals in the final answer
- Do not implement code unless explicitly requested

## Use when
- You want the single most valuable next addition to the project
- You want a concrete recommendation, not a list of ideas

## Required approach
- Inspect the current repository and identify the strongest leverage point across architecture, DX, reliability, performance, security, or delivery speed
- Evaluate candidate ideas by:
    - impact
    - implementation complexity
    - risk
    - reversibility
- Select exactly one proposal with the best overall leverage

## Output
Provide:
- a concise proposal statement
- why this is the best next addition now
- expected business and technical benefits
- key risks and mitigations
- smallest safe implementation plan
- test/validation strategy
- rollout and rollback notes

## Done when
- The recommendation is concrete, measurable, and actionable
- The final answer contains exactly one proposal
- The proposal is justified by impact relative to complexity and risk
