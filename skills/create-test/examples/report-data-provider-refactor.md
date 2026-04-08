# Example: Tests Simplified with Data Providers

## Target: `src/Validators/AddressValidator.php`

| Field | Value |
|---|---|
| **Decision** | Tests refactored |
| **Coverage** | 100% (unchanged) |
| **Tests removed** | 4 (replaced by data provider) |
| **Tests added** | 1 (data-provider-driven) |
| **Net test count change** | -3 |
| **Flaky check** | Passed |

### Before

- `it rejects empty street` — standalone test
- `it rejects empty city` — standalone test
- `it rejects empty zip` — standalone test
- `it rejects empty country` — standalone test

### After

- `it rejects invalid address` with `invalidAddressProvider` covering all 4 cases

### Rationale

All four tests shared identical structure (set one field empty, assert validation fails). A data provider reduces duplication and makes adding new cases trivial.
