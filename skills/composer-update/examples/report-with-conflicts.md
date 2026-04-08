# Example: Update Report With Conflicts

## Conflicts

2 conflicts detected:

1. **symfony/http-kernel** requires `psr/log ^2.0` but **monolog/monolog 2.x** requires `psr/log ^1.0`.
   Resolution: upgrade monolog to 3.x or constrain symfony/http-kernel to ~6.2.

2. **doctrine/orm** was downgraded from 2.17.0 to 2.16.2 because `vendor/custom-bundle` pins `doctrine/orm <2.17`.
   Resolution: update `vendor/custom-bundle` or relax the constraint.

## Updated Packages

### symfony/http-kernel (6.3.5 -> 6.4.1)

- **Breaking**: Removed deprecated `KernelInterface::getRootDir()`.
- **New**: Added native return type declarations.

### guzzlehttp/guzzle (7.7.0 -> 7.8.1)

- **Fix**: Fixed redirect URI resolution for relative paths.
- **Fix**: Corrected content-length header on retry.

### vendor/custom-bundle (1.2.0 -> 1.2.0)

- No changelog found in vendor or linked repository.

## Suggested Follow-up

- Resolve the 2 conflicts listed above before deploying.
- Run `composer validate` to verify composer.json consistency.
- Run `composer audit` to check for security advisories.
- Run the project test suite to verify compatibility.
