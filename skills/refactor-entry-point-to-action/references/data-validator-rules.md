# Data Validator Rules

## Why validators exist

Actions must not contain inline validation logic. All input validation is extracted into a dedicated Data Validator class to keep the Action focused on orchestration.

## Placement

- Data Validators live under `app/DataValidators/{Domain}/`.

## Class constraints

- Data Validators are `final readonly` classes.
- Constructor dependency injection is required.
- Expose a single `validate()` method that throws `ValidationException` on failure.

## Prohibited patterns in Actions

- Do not throw `ValidationException` directly inside an Action.
- Do not call `Validator::make()` inside an Action.

## Integration with Actions

- Actions call the Data Validator **before** proceeding with business orchestration.
- The validator is injected via the Action constructor.
