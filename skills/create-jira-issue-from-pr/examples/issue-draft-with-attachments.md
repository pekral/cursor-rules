# Example: JIRA Issue Draft With Attachments

Source PR: #145 — feat(reports): add PDF export for monthly summaries

---

## Goal

Add the ability to export monthly summaries to PDF format according to the attached template.

## Original Assignment (unchanged)

Customers need to export monthly summaries to PDF. The template is in the attachment (screenshot). The export must include a header with the company logo and a summary table.

## Technical Context from PR

- PR adds a new `PdfExportService` in `src/Services/Export/`
- Screenshot in PR shows the required layout: header with logo, table with 5 columns
- Review from @tech-lead requires lazy loading for large datasets (> 1000 rows)
- Attachment (screenshot) successfully downloaded and analyzed

## Implementation Requirements

- [ ] Implement `PdfExportService` with header and table layout support
- [ ] Add lazy loading for datasets over 1000 rows
- [ ] Integrate company logo into the export header
- [ ] Add endpoint `GET /reports/monthly/{id}/pdf`

## Acceptance Criteria

- [ ] PDF export contains header with logo and table according to template
- [ ] Export of a dataset with 5000 rows completes within 10 seconds
- [ ] PDF is valid and opens in common browsers (Chrome, Firefox)
- [ ] Endpoint returns 404 for non-existent report

## Notes

- Source: https://github.com/org/repo/pull/145
- Attachment (layout screenshot) was analyzed and requirements from it are included above.
- Output is formatted for JIRA issue, original assignment content remains unchanged.
