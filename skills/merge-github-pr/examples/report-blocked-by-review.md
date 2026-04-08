# Example: PR Blocked by Review

## PR #158 — feat(export): add CSV streaming for large datasets

| Field | Value |
|---|---|
| **Decision** | `do not merge` |
| **CI** | All checks passed |
| **Conflicts** | None |
| **Reviews** | 1 changes requested |
| **Unresolved threads** | 2 |

### Unresolved items

- [ ] **src/Export/CsvExporter.php:72** — Missing chunk size limit; unbounded memory for large exports (reviewer: @senior-dev)
- [ ] **tests/Export/CsvExporterTest.php** — No test for datasets exceeding 10k rows (reviewer: @senior-dev)

### Blocking reason

`CHANGES_REQUESTED` review from @senior-dev has not been followed by an `APPROVED` review. Two inline threads remain unresolved.

### Next action

Resolve the review feedback, push fixes, and request re-review.
