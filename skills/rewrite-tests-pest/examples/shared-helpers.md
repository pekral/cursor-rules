# Example: Shared Helpers in Pest.php

## Pest.php

```php
uses(Tests\TestCase::class)->in('Feature');

function bindSparkpostMailerNever(Application $app): void
{
    $app->bind(SparkpostMailer::class, fn () => Mockery::mock(SparkpostMailer::class, function ($mock) {
        $mock->shouldNotReceive('send');
    }));
}

function createAuthenticatedUser(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    test()->actingAs($user);

    return $user;
}
```

## Test file using shared helpers

```php
beforeEach(function () {
    bindSparkpostMailerNever($this->app);
    $this->user = createAuthenticatedUser(['role' => 'admin']);
});

it('does not send email on dry run', function () {
    $this->post('/api/notifications/dry-run', ['message' => 'test']);

    // No email sent — enforced by the mock in Pest.php
    expect(true)->toBeTrue();
});
```

### Key points

- Shared helpers live in `Pest.php`, not duplicated across test files
- Use `test()->methodName()` when calling methods from abstract test classes
- Keep helpers focused and reusable
