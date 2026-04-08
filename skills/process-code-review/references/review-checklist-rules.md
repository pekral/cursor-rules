# Review Checklist Rules

## Building the checklist

- Locate code review output and all review comments (including review threads and general comments) in each PR.
- If there is only a generic `CR` comment, treat it as `code review` feedback.
- Build a checklist from all review findings and map each item to a concrete code or test change.
- Ensure the checklist explicitly contains all reported **DRY violations** and tracks their resolution before triggering the next CR cycle.

## Scope of changes

- Apply only the requested changes — keep scope limited to review feedback.
- All new or modified production code must follow @skills/class-refactoring/SKILL.md.

## Simplification analysis

- Evaluate whether the solution can be written more simply without altering the new logic, leveraging rules and conventions already defined in `rules/**/*.mdc`.
- Flag unnecessary complexity as a finding.
