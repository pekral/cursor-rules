# Example: Report with Failures

## Test Report — PR #103 "Improve invoice export"

### Tested Scenarios

## Scenario — Export Single Invoice as PDF

**What was tested**
User exports a single invoice from the invoice detail page.

**Expected result**
A PDF file downloads containing the correct invoice data with proper formatting.

**Observed result**
PDF downloaded successfully with correct data and layout.

**Status**
Passed

**Comment**
Works as expected. The PDF is clean and professional.

---

## Scenario — Bulk Export Multiple Invoices

**What was tested**
User selects 5 invoices from the list and clicks "Export selected."

**Expected result**
A ZIP file downloads containing 5 individual PDF files.

**Observed result**
The export started but the browser showed a loading spinner indefinitely. After 30 seconds, no file was downloaded.

**Status**
Failed

**Comment**
Bulk export appears broken — the user is left waiting without feedback or an error message. Single export works fine, so the issue is specific to the bulk operation.

---

## Scenario — Export with Date Filter

**What was tested**
User filters invoices by date range and exports the filtered results.

**Expected result**
Only invoices within the selected date range are included in the export.

**Observed result**
Could not test — the date filter dropdown did not open when clicked.

**Status**
Blocked

**Comment**
The date filter UI component appears unresponsive, preventing this scenario from being tested.

---

### Overall Summary

1 of 3 scenarios passed. Bulk export is broken (no download occurs), and the date filter is unresponsive, blocking one scenario.

### Failed / Blocked Behaviors

- **Bulk export** — hangs indefinitely with no file downloaded and no error shown
- **Date filter** — dropdown does not open, blocking the filtered export test

### Recommendation

The change is **not ready** from a user perspective. The bulk export failure is a significant issue that affects core functionality.
