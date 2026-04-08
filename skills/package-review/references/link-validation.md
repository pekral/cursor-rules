# Link Validation

## Scope
All URLs found in the following locations must be checked:
- `composer.json` fields: `homepage`, `support.issues`, `support.source`, `support.docs`
- `README.md` and other documentation files
- Author entries with `homepage` fields

## Validation Rules
- Each URL must return an HTTP 2xx or 3xx status code
- URLs returning 4xx or 5xx are flagged as broken
- Timeout threshold: 10 seconds per request
- Redirects are acceptable but should be noted if they redirect to unexpected destinations

## Reporting
- List all checked URLs with their status
- Group by: valid, redirected, broken, unreachable
- Broken links are blocking issues that must be fixed
