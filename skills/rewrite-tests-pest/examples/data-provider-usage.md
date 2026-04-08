# Example: Using Data Providers

## Before (repetitive tests)

```php
it('validates that empty name is rejected', function () {
    expect(fn () => new UserName(''))->toThrow(InvalidArgumentException::class);
});

it('validates that name exceeding max length is rejected', function () {
    expect(fn () => new UserName(str_repeat('a', 256)))->toThrow(InvalidArgumentException::class);
});

it('validates that name with special characters is rejected', function () {
    expect(fn () => new UserName('<script>'))->toThrow(InvalidArgumentException::class);
});
```

## After (data provider)

```php
it('rejects invalid names', function (string $invalidName) {
    expect(fn () => new UserName($invalidName))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty string'          => [''],
    'exceeds max length'    => [str_repeat('a', 256)],
    'special characters'    => ['<script>'],
]);
```

### Key changes

- Three nearly identical tests collapsed into one with a data provider
- Each dataset is named for clarity in test output
- Only the varying input is in the provider; the assertion logic is shared
