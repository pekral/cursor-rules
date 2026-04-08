# Example: Issues Created from Code Review

## Source

PR #203 — feat(export): add bulk CSV export endpoint

## Summary

| Field | Value |
|---|---|
| **Source PR** | #203 — feat(export): add bulk CSV export endpoint |
| **Total findings reviewed** | 5 |
| **Issues created** | 2 |
| **Duplicates skipped** | 1 |
| **Findings excluded** | 2 |

## Issues created

1. **#312** — [fix(export): add chunk size limit to prevent unbounded memory usage](https://github.com/org/repo/issues/312)
   - Severity: Critical
   - File: `src/Export/CsvExporter.php:72`
   - Reviewer: @senior-dev
   - Labels: `bug`, `from-code-review`

2. **#313** — [test(export): add coverage for datasets exceeding 10k rows](https://github.com/org/repo/issues/313)
   - Severity: Moderate
   - File: `tests/Export/CsvExporterTest.php`
   - Reviewer: @senior-dev
   - Labels: `test`, `from-code-review`

## Duplicates skipped

- **Missing input validation on export filters** — duplicate of #289 (fix(export): validate filter parameters before query execution)

## Findings excluded

- **Rename variable `$tmp` to `$temporaryFile`** — Minor stylistic nitpick, no functional impact
- **"Good use of streaming here"** — Praise comment, not a finding
