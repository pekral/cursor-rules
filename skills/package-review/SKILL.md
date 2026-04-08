---
name: package-review
description: "Use when reviewing composer.json packages. Validates structure, checks required fields, verifies links, and ensures proper configuration of autoloading, dependencies, and metadata."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- Apply @rules/base-constraints.mdc
- All messages formatted as markdown for output.
- If you are not on the main git branch in the project, switch to it.

**Scripts:** Use the pre-built scripts in `@skills/package-review/scripts/` to gather data. Do not reinvent these queries — run the scripts directly.

| Script | Purpose |
|---|---|
| `scripts/validate-composer.sh [path]` | Validate composer.json structure, required/recommended fields, and PSR-4 directories |
| `scripts/check-links.sh [dir]` | Check all URLs in composer.json and README.md for reachability |

**References:**
- `references/required-fields.md` — required composer.json fields: name, description, type, license, authors, require, autoload
- `references/recommended-fields.md` — recommended fields: keywords, homepage, support, require-dev, scripts
- `references/link-validation.md` — URL validation scope, rules, and reporting format

**Examples:** See `examples/` for expected output format:
- `examples/report-all-passed.md` — clean review with no issues
- `examples/report-issues-found.md` — review with errors and warnings

**Steps:**
1. Run `scripts/validate-composer.sh` to check structure and field presence.
2. Run `scripts/check-links.sh` to verify all URLs in documentation and composer.json.
3. Review the quality of the `composer.json` content per `references/required-fields.md`:
   - Verify that all required fields are present and correct.
   - Validate version constraints, autoload mappings, and SPDX license identifiers.
4. Check recommended fields per `references/recommended-fields.md`:
   - Determine whether optional but useful fields are set.
   - Flag missing recommended fields as warnings.
5. Validate all links per `references/link-validation.md`:
   - Find all links in documentation and composer.json.
   - Verify that each link is functional.
6. Refresh readme.md file for current changes — do not rewrite all, just only merge or delete file content.

**Output contract:** For each reviewed package, produce a structured report containing:

| Field | Required | Description |
|---|---|---|
| Package name | Yes | The `vendor/package` identifier |
| Required fields status | Yes | OK / MISSING / ERROR for each required field |
| Recommended fields status | Yes | OK / MISSING for each recommended field |
| Link validation | Yes | Status of all checked URLs |
| Issues summary | If any | Numbered list of errors, warnings, and info items |
| Decision | Yes | Package is ready / has blocking issues / has warnings only |
| Confidence notes | If applicable | Caveats or assumptions (e.g., unreachable URLs due to network) |
| Suggested fixes | If issues | Actionable steps to resolve each issue |
