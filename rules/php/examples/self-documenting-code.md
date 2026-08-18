# Self-Documenting Code Examples

### Avoid: a *why* comment narrating a condition built from unnamed literals

```php
$firstFailedGuard = Arr::get($exception->guards(), '0');

// The `passport` guard is a bearer-token guard (MCP/OAuth clients among
// others) and must never answer with a browser login redirect: without a
// 401 + WWW-Authenticate challenge the client cannot discover the
// authorization server and the OAuth flow never starts (ECOMAIL-6655).
if ($request->expectsJson() || $firstFailedGuard === 'passport') {
    return response()->json(['error' => 'Unauthenticated.'], 401);
}
```

Prefer naming the literal and extracting the condition, keeping only the residue no name can carry:

```php
private const string BEARER_TOKEN_GUARD = 'passport';

if ($request->expectsJson() || $this->expectsTokenChallenge($exception)) {
    return response()->json(['error' => 'Unauthenticated.'], 401);
}

/**
 * @see ECOMAIL-6655
 */
private function expectsTokenChallenge(AuthenticationException $exception): bool
{
    return Arr::get($exception->guards(), '0') === self::BEARER_TOKEN_GUARD;
}
```

### Avoid: a magic value explained by a comment

```php
// 3 retries — Stripe recommends this before giving up on a webhook delivery
if ($attempt >= 3) {
    return;
}
```

Prefer naming the value:

```php
private const int MAX_WEBHOOK_DELIVERY_ATTEMPTS = 3;

if ($attempt >= self::MAX_WEBHOOK_DELIVERY_ATTEMPTS) {
    return;
}
```

### Avoid: a comment narrating a step instead of naming it

```php
// normalize the phone number before matching it against existing customers
$normalized = preg_replace('/[^\d+]/', '', $phone);
$normalized = ltrim($normalized, '+');
```

Prefer extracting an intention-revealing method:

```php
$normalized = $this->normalizePhoneNumber($phone);
```
