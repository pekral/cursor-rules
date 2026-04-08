# Example: Consolidated Multi-PR Report

## Overview — PROJ-1234

| PR | Branch | Result |
|---|---|---|
| #201 | feature/PROJ-1234-backend | Has findings (1 Critical, 1 Moderate) |
| #205 | feature/PROJ-1234-frontend | Clean |
| #208 | feature/PROJ-1234-migrations | Skipped — merge conflicts |

## PR #201 — feature/PROJ-1234-backend

### Critical

- **app/Services/PaymentService.php:142** — Race condition on balance update. Use `lockForUpdate()`.

### Moderate

- **app/Models/Order.php:34** — DRY violation: duplicates `ShippingService::computeFee()`.

## PR #205 — feature/PROJ-1234-frontend

No findings were identified.

## PR #208 — feature/PROJ-1234-migrations

Code review skipped due to merge conflicts with the base branch. Resolve conflicts and re-request review.
