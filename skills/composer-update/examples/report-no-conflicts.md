# Example: Clean Update Report

## Conflicts

No conflicts detected.

## Updated Packages

### symfony/console (6.3.4 -> 6.4.0)

- **Breaking**: Deprecated `Command::setHidden()` in favor of `#[AsCommand(hidden: true)]` attribute.
- **New**: Added `SignalableCommandInterface` support for graceful shutdowns.
- **Fix**: Fixed progress bar rendering in non-interactive mode.

### monolog/monolog (3.4.0 -> 3.5.0)

- **New**: Added `JsonFormatter::BATCH_MODE_NEWLINES` for NDJSON output.
- **Fix**: Fixed `StreamHandler` file permission race condition.

### doctrine/dbal (3.7.1 -> 3.7.2)

- **Fix**: Fixed parameter type detection for nullable columns.
- **Fix**: Corrected schema diff for PostgreSQL partial indexes.

## Suggested Follow-up

- Run `composer validate` to verify composer.json consistency.
- Run `composer audit` to check for security advisories.
- Run the project test suite to verify compatibility.
