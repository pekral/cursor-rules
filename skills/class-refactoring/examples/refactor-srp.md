# Example: Splitting Responsibilities (SRP)

## Before

```php
class OrderService
{
    public function createOrder(array $items): Order
    {
        $order = Order::create(['status' => 'pending']);
        foreach ($items as $item) {
            $order->items()->create($item);
        }

        // Calculate total
        $total = $order->items->sum('price');
        $order->update(['total' => $total]);

        // Send notification
        Mail::to($order->user)->send(new OrderCreatedMail($order));

        return $order;
    }
}
```

## After

```php
class CreateOrderAction
{
    public function __construct(
        private readonly OrderCalculator $calculator,
        private readonly OrderNotifier $notifier,
    ) {}

    public function __invoke(array $items): Order
    {
        $order = Order::create(['status' => 'pending']);
        foreach ($items as $item) {
            $order->items()->create($item);
        }

        $total = $this->calculator->calculateTotal($order);
        $order->update(['total' => $total]);

        $this->notifier->notifyCreated($order);

        return $order;
    }
}
```

## What Changed
- Extracted calculation logic to `OrderCalculator`.
- Extracted notification logic to `OrderNotifier`.
- Converted service to single-purpose Action.

## Why
- Original class had three responsibilities: persistence, calculation, notification.
- Each concern can now be tested and modified independently.
- Action pattern follows project architecture conventions.
