# Example: Review Processing Blocked

## PR #53 — feat(notifications): add SMS channel support

| Field | Value |
|---|---|
| **Task** | GH-119 |
| **PR** | #53 |
| **Decision** | Blocked |
| **CR findings** | 1 Critical remaining |
| **Tests** | 2 failing |
| **Remaining blockers** | Merge conflict with `main`; Critical finding unresolved |

### Review checklist

- [x] Extract notification interface — resolved in `src/Notifications/ChannelInterface.php`
- [ ] **CRITICAL:** SMS credentials must not be hardcoded — `src/Notifications/SmsChannel.php:28` still contains inline key
- [x] Add integration test for SMS delivery — resolved in `tests/Notifications/SmsChannelTest.php:44`

### Blocking reasons

1. PR has merge conflicts with the base branch — cannot proceed until resolved.
2. Critical finding (hardcoded credentials) not yet fixed.

### Next action

Resolve merge conflicts, fix the hardcoded credentials issue, then re-run the CR cycle.
