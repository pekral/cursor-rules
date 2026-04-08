# Example: Before and After PEST Rewrite

## Before (PHPUnit)

```php
class UserServiceTest extends TestCase
{
    public function test_it_throws_when_user_not_found(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('find')->willReturn(null);
        $service = new UserService($repo);

        $this->expectException(UserNotFoundException::class);
        $service->getUser(999);
    }

    public function test_it_returns_user(): void
    {
        $user = new User(id: 1, name: 'Alice');
        $repo = $this->createMock(UserRepository::class);
        $repo->method('find')->willReturn($user);
        $service = new UserService($repo);

        $result = $service->getUser(1);

        $this->assertSame($user, $result);
    }
}
```

## After (PEST)

```php
// Error cases first, then happy paths
it('throws when user is not found', function () {
    $repo = Mockery::mock(UserRepository::class);
    $repo->shouldReceive('find')->with(999)->andReturnNull();

    $service = new UserService($repo);

    expect(fn () => $service->getUser(999))
        ->toThrow(UserNotFoundException::class);
});

it('returns the user', function () {
    $user = new User(id: 1, name: 'Alice');
    $repo = Mockery::mock(UserRepository::class);
    $repo->shouldReceive('find')->with(1)->andReturn($user);

    $service = new UserService($repo);

    expect($service->getUser(1))->toBe($user);
});
```

### Key changes

- Converted class-based test to PEST closure syntax
- Error case placed first
- Arrange-act-assert pattern preserved
- No `covers()` method generated
