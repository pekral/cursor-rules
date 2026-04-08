# Example: Successful Refactor

## Refactor OrderController::store to Action

| Field | Value |
|---|---|
| **Entry point** | `OrderController::store` in `app/Http/Controllers/OrderController.php` |
| **Action created** | `app/Actions/Order/CreateOrderAction.php` |
| **Data Validator** | `app/DataValidators/Order/CreateOrderDataValidator.php` |
| **Decision** | Refactor complete |
| **Review findings** | 0 critical, 0 medium |
| **Quality checks** | All passed |

### Changes summary

- Moved orchestration from `OrderController::store` into `CreateOrderAction::__invoke()`
- Extracted validation into `CreateOrderDataValidator::validate()`
- Controller now delegates via `$action($request)`
- Reads delegated to `OrderRepository`, writes to `OrderModelManager`

### Tests

- [x] `tests/Feature/Order/CreateOrderTest.php` -- updated to cover Action flow
- [x] `tests/Unit/Actions/Order/CreateOrderActionTest.php` -- new unit test for Action
- [x] `tests/Unit/DataValidators/Order/CreateOrderDataValidatorTest.php` -- new

### Next action

Run full test suite and open PR.
