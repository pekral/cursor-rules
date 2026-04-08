# Example: Bugfix Branch Summary

## Summary of changes — fix/invoice-rounding

### What changed
I fixed a rounding error in invoice total calculation that caused discrepancies of up to 1 cent on multi-line invoices. The issue affected roughly 3% of invoices generated since the last pricing update. This fix ensures financial reports match actual charges.

### Changes by category

#### Bug fixes
- Corrected rounding to use banker's rounding (round-half-to-even) instead of truncation (`src/Billing/InvoiceCalculator.php`)
- Fixed tax line rounding to apply after subtotal aggregation (`src/Billing/TaxCalculator.php`)

#### Tests
- Added edge-case tests for multi-line invoice rounding (`tests/Billing/InvoiceCalculatorTest.php`)
- Regression test with known failing invoice from production data (`tests/Billing/RoundingRegressionTest.php`)

### Breaking changes
API response field `invoice.total` may now differ by up to 1 cent from previously returned values. Consumers that cache or compare invoice totals should be aware.

### Testing notes
Ran recalculation against 500 production invoices in staging. All discrepancies resolved. Recommend monitoring billing alerts for 48 hours after deployment.
