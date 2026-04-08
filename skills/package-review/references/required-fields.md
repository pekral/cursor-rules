# Required Fields

These fields must be present and correct in `composer.json`.

## `name`
- Must follow `vendor/package` format
- Lowercase, alphanumeric, hyphens allowed
- Example: `acme/my-library`

## `description`
- Clear, concise, single-line description of the package purpose
- Must not be empty or generic (e.g., avoid "A PHP package")

## `type`
- Must be a valid Composer package type: `library`, `project`, `metapackage`, `composer-plugin`
- Should match the actual purpose of the package

## `license`
- Must be a valid SPDX license identifier (e.g., `MIT`, `GPL-3.0-only`, `Apache-2.0`)
- Use SPDX short identifier, not full license text
- Reference: https://spdx.org/licenses/

## `authors`
- At least one author entry with `name` field
- `email` is recommended but not strictly required
- `homepage` and `role` are optional but encouraged

## `require`
- Dependencies must use proper version constraints
- Avoid `*` or overly broad constraints
- Prefer caret (`^`) or tilde (`~`) version ranges
- PHP version constraint should be present if applicable

## `autoload`
- PSR-4 autoloading must be configured
- Namespace-to-directory mapping must be correct and consistent
- Verify that the declared directories actually exist
