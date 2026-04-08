# Example: Blocked by Code Review Findings

## Issue #93 — feat(export): add PDF export for invoices

| Field | Value |
|---|---|
| **Task type** | Feature |
| **Decision** | In progress — blocked by CR findings |
| **PR** | Not yet created |
| **Tests** | Passing, 100% coverage on changes |
| **CR findings** | 1 Critical, 1 Moderate |
| **CI status** | Not yet run |

### Critical findings

- **src/Export/PdfGenerator.php:45** — User-supplied filename passed directly to `file_put_contents` without sanitization. Path traversal risk.

### Moderate findings

- **src/Export/PdfGenerator.php:78** — Missing error handling for template rendering failure. Exception leaks stack trace.

### Next action

Fix both findings, then re-run code review cycle. PR creation is blocked until CR is clean.
