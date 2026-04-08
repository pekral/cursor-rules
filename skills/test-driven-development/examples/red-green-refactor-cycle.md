# Example: Red-Green-Refactor Cycle

## Feature: Registration rejects empty email

### Step 1 -- RED: Write failing test

```php
it('rejects empty email on registration', function (): void {
    $response = $this->postJson('/api/register', [
        'email' => '',
        'password' => 'SecurePass123!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
```

### Step 2 -- Verify RED

```
$ php artisan test --filter="rejects empty email"

FAIL  Tests\Feature\RegistrationTest > rejects empty email on registration
Expected status code 422, got 200.
```

Test fails because validation is not yet implemented. This is the expected reason.

### Step 3 -- GREEN: Minimal implementation

Added `'email' => ['required', 'email']` validation rule to `RegisterController`.

### Step 4 -- Verify GREEN

```
$ php artisan test --filter="rejects empty email"

PASS  Tests\Feature\RegistrationTest > rejects empty email on registration
Tests: 1 passed
```

### Step 5 -- REFACTOR

Extracted validation rules to a `RegisterRequest` form request class. Re-ran tests to confirm they still pass.

### Step 6 -- Next cycle

Proceed to next behavior: "rejects invalid email format".
