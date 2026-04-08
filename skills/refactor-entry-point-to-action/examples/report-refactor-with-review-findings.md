# Example: Refactor with Review Findings

## Refactor UserController::update to Action

| Field | Value |
|---|---|
| **Entry point** | `UserController::update` in `app/Http/Controllers/UserController.php` |
| **Action created** | `app/Actions/User/UpdateUserAction.php` |
| **Data Validator** | `app/DataValidators/User/UpdateUserDataValidator.php` |
| **Decision** | Refactor complete (after fixes) |
| **Review findings** | 1 critical (fixed), 1 medium (fixed) |
| **Quality checks** | All passed after second run |

### Review findings (resolved)

1. **Critical:** Action contained direct `Validator::make()` call -- moved to `UpdateUserDataValidator`
2. **Medium:** Missing PHPDoc on `__invoke()` return type -- added `@return UserResource`

### Changes summary

- Moved orchestration from `UserController::update` into `UpdateUserAction::__invoke()`
- Single-use `UserService::updateProfile()` inlined into Action (single-use rule)
- Extracted validation into `UpdateUserDataValidator::validate()`
- Controller delegates via `$action($id, $request)`

### Tests

- [x] `tests/Feature/User/UpdateUserTest.php` -- updated
- [x] `tests/Unit/Actions/User/UpdateUserActionTest.php` -- new

### Next action

All findings resolved. Run full test suite and open PR.
