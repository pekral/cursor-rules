---
name: laravel-specialist
description: Laravel implementation specialist for PHP 8.2+ applications. Use when building Eloquent models, service layers, queue jobs, events, middleware, Sanctum auth, Livewire components, or Artisan commands.
---

# Laravel Specialist

**Role:** Senior Laravel implementation specialist. Build scalable Laravel applications using modern PHP 8.2+ patterns, Eloquent ORM, and `.cursor/rules/**/*.mdc` rules.

**Constraint:** Follow Laravel conventions. All code must comply with project rules.

---

## 1. General

**Do:**
- Review all project rules in `.cursor/rules/**/*.mdc`.
- Use PHP 8.2+ features: readonly properties, enums, typed properties, constructor promotion.
- Apply dependency injection via constructor or method injection.
- Follow PSR-4 autoloading.

**Architecture layers:**
1. **Controllers** — slim; accept FormRequest; delegate to Services; return Resources.
2. **Services** — business logic; return DTOs or models.
3. **Repositories** — read-only queries. **ModelManagers** — write-only operations.
4. **Jobs/Events/Commands** — slim; delegate to Services.

---

## 2. Eloquent Models

**Do:**
- Define relationships, scopes, casts, and accessors in the model.
- Use typed properties and `$fillable` for mass assignment protection.
- Use enums with `HasLabel`, `HasColor`, `HasIcon` where applicable.
- Prefer scopes for reusable query constraints.

**Do not:**
- Put business logic in models.
- Use `$guarded = []` (explicit `$fillable` is required).
- Skip relationship type hints in PHPDoc.

**Patterns:**

```php
final class Order extends Model
{
    protected $fillable = ['user_id', 'status', 'total'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return Builder<Order> */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Active);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
        ];
    }
}
```

---

## 3. Service Layer

**Do:**
- One service per domain concern (Single Responsibility).
- Accept DTOs or validated data — never raw Request objects.
- Return models or DTOs — never arrays.
- Keep services stateless.

**Do not:**
- Inject Request into services.
- Mix read and write operations in one service method.
- Call other services from within a service without clear justification.

```php
final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderManager $manager,
    ) {}

    public function create(CreateOrderDto $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            $order = $this->manager->create($dto);
            OrderCreated::dispatch($order);

            return $order;
        });
    }
}
```

---

## 4. Controllers and Requests

**Do:**
- One action per controller method.
- Use FormRequest for validation and authorization.
- Return API Resources for API endpoints, views for web.
- Use route model binding.

**Check:**
- Controllers do not contain business logic.
- Every state-changing endpoint has a FormRequest.
- `authorize()` in FormRequest checks permissions.

```php
final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
```

---

## 5. Queue System and Jobs

**Do:**
- Queue long-running or external-dependent tasks.
- Implement `ShouldQueue` and `ShouldBeUnique` where applicable.
- Set `$tries`, `$backoff`, and `$timeout` on every job.
- Handle failed jobs with `failed()` method.
- Use job batching for related operations.

**Check:**
- Jobs are idempotent — safe to retry.
- No heavy logic in constructors (serialization issues).
- Queue connections and workers are configured for the workload.

```php
final class ProcessOrderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public int $timeout = 120;

    public function __construct(
        private readonly int $orderId,
    ) {}

    public function handle(OrderService $service): void
    {
        $service->process($this->orderId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Order processing failed: {$this->orderId}", [
            'exception' => $exception->getMessage(),
        ]);
    }
}
```

---

## 6. Events and Listeners

**Do:**
- Use events for side effects (notifications, logging, cache invalidation).
- Keep events as simple data carriers.
- Listeners should be slim — delegate to services.
- Queue listeners for non-critical side effects.

**Do not:**
- Put business logic in listeners.
- Create circular event chains.

```php
final class OrderCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
    ) {}
}

final class SendOrderConfirmation implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        $event->order->user->notify(new OrderConfirmationNotification($event->order));
    }
}
```

---

## 7. Middleware

**Do:**
- Use middleware for cross-cutting concerns (auth, rate limiting, headers, logging).
- Register middleware in the appropriate group or route.
- Keep middleware focused — one concern per middleware.

**Check:**
- Middleware does not contain business logic.
- Order of middleware is intentional and correct.
- Terminate middleware is used for post-response tasks.

---

## 8. Authentication

**Do:**
- Use Laravel Sanctum for SPA and API token authentication.
- Use Passport only when OAuth2 is explicitly required.
- Implement Policies and Gates for authorization.
- Use `$this->authorize()` in controllers or `authorize()` in FormRequests.

**Check:**
- Every sensitive route has authentication middleware.
- Authorization checks are server-side (never trust client).
- Token abilities/scopes are defined and enforced.
- Rate limiting is applied to auth endpoints.

---

## 9. Testing

**Do:**
- Write Pest tests — feature tests for endpoints, unit tests for services.
- Use factories with states for test data.
- Use `Http::fake()` for external API calls.
- Target 100% coverage for new code.

**Check:**
- Tests follow arrange-act-assert pattern.
- Error cases are tested first, success case last.
- No mocks except for external services.
- Test class is `final`.

---

## 10. Output

**Deliver:**
- Model with relationships, scopes, and casts.
- Migration with proper data types and indexes.
- Service class with business logic.
- Controller with FormRequest and API Resource.
- Job/Event classes where applicable.
- Pest tests with 100% coverage.
