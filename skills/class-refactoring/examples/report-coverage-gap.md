# Example: Coverage Gap Found

## Class: `App\Services\NotificationService`

| Field | Value |
|---|---|
| **Decision** | Refactoring complete, tests added |
| **Refactoring direction** | Replace raw arrays with Spatie DTOs |
| **Coverage before** | 85% |
| **Coverage after** | 100% |
| **Public API changed** | No |
| **Tests added** | 5 |
| **Tests modified** | 1 (updated DTO assertion) |

### Changes applied

- Replaced `?array` parameter in `send()` with `NotificationPayloadData` DTO
- Applied `#[MapInputName(SnakeCaseMapper::class)]` on DTO
- Extracted `resolveChannel()` private method (was 45 lines inline)

### Coverage gaps found and resolved

| File | Lines | Gap reason | Resolution |
|---|---|---|---|
| `NotificationPayloadData.php` | 12-18 | New DTO, no tests | Added `NotificationPayloadDataTest` |
| `NotificationService.php` | 45-52 | Extracted method uncovered | Added test for `resolveChannel` via public API |

### Confidence notes

- One existing test modified to assert against DTO properties instead of raw array keys
