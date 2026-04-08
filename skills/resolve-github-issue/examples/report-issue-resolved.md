# Example: Issue Resolved Successfully

## Issue #87 — fix(cart): discount not applied to bundled products

| Field | Value |
|---|---|
| **Task type** | Bug |
| **Decision** | Resolved |
| **PR** | #102 |
| **Tests** | All passing, 100% coverage on changes |
| **CR findings** | None (clean after 1 iteration) |
| **CI status** | All checks passed |

### Resolution summary

Discount calculation in `src/Cart/DiscountService.php:134` skipped bundled items due to a missing type check. Added `BundleProduct` to the eligible types array.

### TDD flow

1. Wrote failing test `tests/Cart/DiscountServiceTest.php:testBundledProductDiscount` — confirmed red
2. Applied fix in `src/Cart/DiscountService.php:134`
3. Test confirmed green

### Testing recommendations

- Verify bundled product discount at [/cart?bundle=summer-pack](https://app.example.com/cart?bundle=summer-pack)
- Verify standalone product discount still works at [/cart?product=widget-1](https://app.example.com/cart?product=widget-1)

### Next action

PR #102 is ready for review.
