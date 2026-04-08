# Example: Small Improvement

## Before

```php
public function getDiscountedPrice(float $price, float $discount): float
{
    $result = $price - ($price * $discount / 100);
    if ($result < 0) {
        $result = 0;
    }
    return $result;
}
```

## After

```php
public function getDiscountedPrice(float $price, float $discountPercent): float
{
    return max(0, $price * (1 - $discountPercent / 100));
}
```

## What Changed
- Renamed `$discount` to `$discountPercent` for clarity.
- Replaced manual clamping with `max()`.
- Simplified arithmetic to a single expression.

## Why
- Naming reveals that the parameter is a percentage, not an absolute value.
- `max()` is a standard pattern for floor clamping — no comment needed.
- No behavioral change — same input produces same output.
