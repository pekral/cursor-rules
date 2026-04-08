# Example: Package Review — All Passed

## Package: `acme/http-client`

| Field | Status | Notes |
|---|---|---|
| `name` | OK | `acme/http-client` |
| `description` | OK | "A lightweight HTTP client for PHP" |
| `type` | OK | `library` |
| `license` | OK | `MIT` |
| `authors` | OK | 1 author with name and email |
| `require` | OK | PHP ^8.1, psr/http-client ^1.0 |
| `autoload` | OK | PSR-4: `Acme\HttpClient\` -> `src/` |

### Recommended Fields

| Field | Status | Notes |
|---|---|---|
| `keywords` | OK | http, client, psr-18 |
| `homepage` | OK | https://github.com/acme/http-client |
| `support` | OK | issues, source |
| `require-dev` | OK | phpunit/phpunit ^10.0 |
| `scripts` | OK | test, analyse |

### Link Validation

| URL | Status |
|---|---|
| https://github.com/acme/http-client | 200 OK |
| https://github.com/acme/http-client/issues | 200 OK |

### Decision

Package configuration is complete and correct. No issues found.
