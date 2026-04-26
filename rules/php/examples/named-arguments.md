# Named Arguments Examples

### Good: named arguments clarify unclear scalar values

```php
Http::retry(times: 3, sleepMilliseconds: 100);
```

```php
Str::limit(
    value: $description,
    limit: 160,
    end: '...',
);
```

```php
Cache::put(
    key: $cacheKey,
    value: $result,
    ttl: now()->addMinutes(10),
);
```

### Good: named arguments make boolean flags readable

```php
$report->export(
    format: 'csv',
    includeHeader: true,
    compress: false,
);
```

### Good: named arguments make nullable values explicit

```php
$userService->updateProfile(
    user: $user,
    displayName: $displayName,
    avatarPath: null,
);
```

### Good: named arguments help with repeated scalar types

```php
$invoice->applyDiscount(
    amount: 500,
    currency: 'CZK',
    reason: 'loyalty_discount',
);
```

### Avoid: obvious single-argument calls

```php
$userRepository->find(id: $id);
```

Prefer:

```php
$userRepository->find($id);
```

### Avoid: named arguments do not fix bad method design

```php
$action(
    userId: $user->id,
    productId: $product->id,
    quantity: 2,
    sendEmail: true,
    priority: 'high',
    source: 'admin',
);
```

Prefer DTO/value object:

```php
$action(new CreateOrderData(
    userId: $user->id,
    productId: $product->id,
    quantity: 2,
    sendEmail: true,
    priority: 'high',
    source: 'admin',
));
```

### Avoid: do not reorder arguments just because PHP allows it

```php
Str::limit(
    end: '...',
    value: $description,
    limit: 160,
);
```

Prefer keeping the original signature order:

```php
Str::limit(
    value: $description,
    limit: 160,
    end: '...',
);
```

### Avoid: public API parameter names become part of the contract

```php
// Risky for public package APIs:
// Renaming $sleepMilliseconds later would break users calling it by name.
Http::retry(times: 3, sleepMilliseconds: 100);
```
