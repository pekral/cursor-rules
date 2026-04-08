# Example: Report with Failures

## Test Report — PR #134 "Improve invoice export"

### Tested Scenarios

## Scenario — Export Invoice as PDF

**What was tested**
User exports a single invoice to PDF from the invoice detail page.

**Expected result**
A PDF file downloads containing the invoice data matching what is shown on screen.

**Observed result**
The PDF downloaded correctly and all data matched the on-screen invoice.

**Status**
Passed

**Comment**
Works as expected.

---

## Scenario — Bulk Export with Date Filter

**What was tested**
User selects a date range and exports all invoices within that period.

**Expected result**
A ZIP file downloads containing one PDF per invoice in the selected range.

**Observed result**
The export started but the resulting ZIP contained only 3 of the expected 7 invoices. Invoices from the last two days of the range were missing.

**Status**
Failed

**Comment**
The date filter appears to exclude the end date. A user would expect invoices from the entire selected range, including the last day.

---

## Scenario — Export with No Results

**What was tested**
User selects a date range with no invoices and clicks export.

**Expected result**
A clear message informing the user that there are no invoices to export.

**Observed result**
The page showed a loading spinner for about 10 seconds and then returned to the list without any message.

**Status**
Failed

**Comment**
Confusing experience — the user has no idea what happened. A clear "no results" message is needed.

---

### Overall Summary

1 of 3 scenarios passed. Two failures were found related to date range filtering and empty result handling.

### Failed Scenarios

1. **Bulk Export with Date Filter** — end date appears to be excluded from the range, resulting in missing invoices.
2. **Export with No Results** — no feedback is shown to the user when there are no invoices to export.

### Recommendation

The change is not ready from a user perspective. The two failures should be addressed before merging.
