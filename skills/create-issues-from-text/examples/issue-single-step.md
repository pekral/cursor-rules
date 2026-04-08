# Example: Single Created Issue

## [Step 2/4] Implement UserPreference model and repository

### Goal

Allow the system to store and retrieve user preferences so that users can customize their dashboard experience.

### Original Assignment (context)

See parent issue #45 — "Add user preference support for dashboard customization."

### Technical Solution

- Create `UserPreference` Eloquent model with fields: `user_id`, `key`, `value`, `created_at`, `updated_at`
- Create `UserPreferenceRepository` with methods: `getByUser(int $userId)`, `set(int $userId, string $key, mixed $value)`, `delete(int $userId, string $key)`
- Register repository binding in `AppServiceProvider`
- Add database factory for test data generation

### Acceptance Criteria

- [ ] `UserPreference` model exists with correct fillable fields and casts
- [ ] Repository implements all three methods with proper type hints
- [ ] Factory generates valid test data
- [ ] Unit tests cover repository CRUD operations

### Testing Scenarios

#### Happy Path
- Create a preference for a user, retrieve it, verify the value matches

#### Edge Cases
- Set a preference that already exists — should update, not duplicate
- Delete a preference that does not exist — should not throw an error

#### Regression
- Existing user creation flow must not be affected

### Dependencies

- Step 1 (database migration) must be completed first

### Notes

- Source: https://github.com/org/repo/issues/45
- This issue was created by the `create-issues-from-text` skill.
