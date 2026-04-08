# Example: Partial Skip Due to Duplicates

## Source

PR #221 — refactor(payment): extract payment gateway adapter

## Summary

| Field | Value |
|---|---|
| **Source PR** | #221 — refactor(payment): extract payment gateway adapter |
| **Total findings reviewed** | 4 |
| **Issues created** | 1 |
| **Duplicates skipped** | 2 |
| **Findings excluded** | 1 |

## Issues created

1. **#340** — [security(payment): mask sensitive card data in adapter error logs](https://github.com/org/repo/issues/340)
   - Severity: Critical
   - File: `src/Payment/GatewayAdapter.php:134`
   - Reviewer: @security-lead
   - Labels: `security`, `from-code-review`

## Duplicates skipped

- **Missing retry logic for transient gateway failures** — duplicate of #298 (fix(payment): add retry with exponential backoff for gateway timeouts)
- **PaymentService has too many responsibilities** — duplicate of #215 (refactor(payment): split PaymentService into smaller services)

## Findings excluded

- **Consider extracting the amount formatting into a value object** — Minor suggestion, reviewer marked as optional ("nice-to-have, not blocking")

## Confidence notes

- Duplicate match for #298 is approximate: the existing issue covers gateway timeouts specifically, while the CR finding mentions transient failures broadly. The existing issue likely addresses the concern, but verify after it is resolved.
