# Quality Checklist

Run through this checklist before finalizing any collection update.

## Completeness

- [ ] All changed endpoints from the branch diff are reflected in the collection.
- [ ] No stale endpoints remain for removed routes.
- [ ] No duplicated requests for the same method + path unless intentionally versioned.
- [ ] Request examples reflect actual DTO / FormRequest / validation rules.

## Security

- [ ] All protected routes use variables for credentials.
- [ ] No hard-coded secrets (tokens, passwords, API keys) anywhere in the collection.
- [ ] Environment file contains placeholders only — no real values.

## Structure

- [ ] Folders are organized by domain/module, not by HTTP method.
- [ ] Endpoint names follow `[Resource] Action` style.
- [ ] Collection-level variables include `baseUrl`, `token`, and any required identifiers.

## Validity

- [ ] JSON is valid Postman v2.1 schema.
- [ ] No trailing commas or malformed strings.
- [ ] Collection imports successfully (verified via script or manual check).
- [ ] Collection works with a clean environment file containing placeholders only.

## Reporting

- [ ] Changelog summarizes what was added, changed, and removed.
- [ ] Any TODOs for missing backend behavior are listed.
