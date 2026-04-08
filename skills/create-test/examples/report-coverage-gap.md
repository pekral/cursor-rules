# Example: Coverage Gap Identified

## Target: `src/Notifications/EmailNotifier.php`

| Field | Value |
|---|---|
| **Decision** | Additional tests needed |
| **Coverage before** | 78% |
| **Coverage after** | 100% |
| **Tests added** | 3 |
| **Tests modified** | 1 (added data provider) |
| **Flaky check** | Passed |

### Uncovered paths found

1. `sendBatch()` — early return when recipients list is empty (line 42)
2. `sendBatch()` — retry logic after transient failure (lines 55-60)
3. `formatSubject()` — special character escaping (line 78)

### Changes made

- Added test `it returns early when recipients list is empty`
- Added test `it retries on transient failure` (mocked external SMTP service)
- Added test `it escapes special characters in subject`
- Refactored `invalidRecipientProvider` to cover both empty and malformed inputs

### Next action

Run `@skills/test-like-human/SKILL.md` to validate changes manually.
