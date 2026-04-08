# Conflict Detection Rules

## From Update Output

Look for Composer messages containing any of the following keywords:
- `conflict`
- `Conflict`
- `requires`
- `your requirement`
- `cannot be installed`

These indicate that Composer could not satisfy all version constraints simultaneously.

## Explicit Check

For each updated or important dependency, run:

```bash
composer why-not vendor/package version
```

This shows whether the requested version is blocked by another package's constraint.

## Lock vs Require Comparison

Compare `composer.json` constraints with resolved versions in `composer.lock`:
- Note any package that was **downgraded** from its previously resolved version.
- Note any package that **could not be satisfied** at the requested version.
- Flag cases where the resolved version is at the lower bound of the constraint range.

## Summary Format

- If no issues found: report "No conflicts detected."
- If conflicts exist: list each conflict with:
  - The involved packages
  - The reason (e.g., "X requires Y ^2.0 but Z requires Y ^1.0")
  - Severity (blocking vs advisory)
