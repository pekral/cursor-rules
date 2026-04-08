# Title Generation Rules

Issue titles must follow this pattern:

```
[Step N/Total] <Concise action-oriented title>
```

## Rules

- `N` is the sequential step number (1-based)
- `Total` is the total number of steps in the breakdown
- The title after the prefix must be concise and action-oriented (start with a verb)
- The title must be written in the same language as the original assignment

## Examples

- `[Step 1/5] Create database migration for user preferences`
- `[Step 2/5] Implement UserPreference model and repository`
- `[Step 3/5] Add API endpoints for preference management`
- `[Step 4/5] Build preference settings UI component`
- `[Step 5/5] Add end-to-end tests for preference workflow`

## Anti-patterns

- `[Step 1/5] Step 1` — too vague, no action described
- `[Step 2/5] Do the backend stuff` — not specific enough
- `[Step 3/5] Implement everything for preferences API and also the tests and the validation` — too long, combines multiple concerns
