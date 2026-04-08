# Example: Tests Created Successfully

## Target: `src/Services/PaymentService.php`

| Field | Value |
|---|---|
| **Decision** | Tests created |
| **Coverage** | 100% for changed code |
| **Tests added** | 5 (3 error cases, 2 success cases) |
| **Data providers** | 1 (`invalidPaymentDataProvider`) |
| **Flaky check** | Passed — all tests deterministic |

### Test summary

- `tests/Services/PaymentServiceTest.php`
  - [x] `it throws exception when amount is negative` (error case)
  - [x] `it throws exception when currency is unsupported` (error case)
  - [x] `it throws exception when gateway is unavailable` (error case, mocked external service)
  - [x] `it processes valid payment` (success case)
  - [x] `it applies discount when coupon is valid` (success case)

### Data providers used

- `invalidPaymentDataProvider` — consolidates 2 validation error cases

### Next action

Run `@skills/test-like-human/SKILL.md` to validate changes manually.
