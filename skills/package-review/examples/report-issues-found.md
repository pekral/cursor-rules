# Example: Package Review — Issues Found

## Package: `acme/utils`

| Field | Status | Notes |
|---|---|---|
| `name` | OK | `acme/utils` |
| `description` | MISSING | No description provided |
| `type` | OK | `library` |
| `license` | WARNING | Using deprecated identifier `GPL-3.0`, should be `GPL-3.0-only` |
| `authors` | OK | 1 author |
| `require` | WARNING | Uses `*` constraint for `monolog/monolog` |
| `autoload` | ERROR | PSR-4 maps `Acme\Utils\` to `lib/` but directory does not exist |

### Recommended Fields

| Field | Status | Notes |
|---|---|---|
| `keywords` | MISSING | — |
| `homepage` | MISSING | — |
| `support` | MISSING | — |
| `require-dev` | OK | phpunit/phpunit ^10.0 |
| `scripts` | MISSING | — |

### Link Validation

| URL | Status |
|---|---|
| https://example.com/old-docs | 404 Not Found |

### Issues Summary

1. **ERROR** — `description` is missing (required)
2. **ERROR** — Autoload directory `lib/` does not exist
3. **WARNING** — `license` uses deprecated SPDX identifier
4. **WARNING** — `require` has wildcard constraint for `monolog/monolog`
5. **INFO** — Broken link in README: https://example.com/old-docs

### Decision

Package has blocking issues that must be resolved before publishing.
