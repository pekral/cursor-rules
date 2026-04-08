# Example: Complete JIRA Issue Draft

Source PR: #87 — refactor(api): restructure validation layer

---

## Goal

Restructure the API validation layer so that validations are consistent across all endpoints and reduce the number of duplicate rules.

## Original Assignment (unchanged)

We need to unify input validation on the API. Currently each controller validates on its own and there are duplicates. See comments in PR.

## Technical Context from PR

- PR diff shows inconsistent validation in `src/Controllers/UserController.php` and `src/Controllers/OrderController.php`
- Review from @senior-dev identified missing validation on the `PATCH /orders/{id}` endpoint
- Tests in `tests/Validation/` cover only happy path scenarios

## Implementation Requirements

- [ ] Create shared validation rules in `src/Validation/Rules/`
- [ ] Replace inline validation in all controllers with shared rules
- [ ] Add validation to the `PATCH /orders/{id}` endpoint
- [ ] Add tests for error scenarios (invalid input, missing required fields)

## Acceptance Criteria

- [ ] No controller contains inline validation rules
- [ ] All API endpoints return 422 with error description for invalid input
- [ ] Test coverage of the validation layer is at least 80%
- [ ] Existing API contracts have not changed (backward compatibility)

## Notes

- Source: https://github.com/org/repo/pull/87
- Output is formatted for JIRA issue, original assignment content remains unchanged.
