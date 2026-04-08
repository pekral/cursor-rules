# Example: Issue Resolution Blocked by CI

## PROJ-5678 — feat(export): add bulk CSV export endpoint

| Field | Value |
|---|---|
| **Task type** | Feature |
| **Decision** | Blocked |
| **Tests** | 100% coverage on changed files |
| **CI** | 2 failures (lint, unit tests) |
| **Code review** | Not started (CI must pass first) |
| **PR** | Not created |
| **JIRA status** | In progress |

### Blocking reasons

- `phpcs` reports 3 coding standard violations in `src/Export/CsvExporter.php`
- Unit test `CsvExporterTest::testLargeDataset` fails with memory limit error

### Next action

Fix lint violations and failing test, then re-run CI before proceeding to code review.
