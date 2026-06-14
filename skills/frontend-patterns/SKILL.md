---
name: frontend-patterns
description: "Use when building Livewire/Blade/Alpine UI in a Laravel app — component composition, state placement, performance, forms, and loading/empty/error states."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/laravel/livewire.mdc` — class in `app/Livewire`, view in `resources/views/livewire`, extend `Livewire\Component`; components are slim entry points; delegate business logic to Actions/Services; inject dependencies via `boot()`, never as method params; Blade stays presentation-only.
- Apply `@rules/laravel/filament.mdc` — prefer Filament form/table components for admin UIs; custom Blade+Tailwind needs a registered theme.
- Apply `@rules/laravel/architecture.mdc` — keep query/business logic out of views and components.
- Apply `@rules/sql/optimalize.mdc` — eager-load to avoid N+1 in loops rendered by Blade.
- Stack is Blade + Livewire + Alpine.js + Filament + Tailwind. No React/Vue/Next — never output `useState`/`useEffect`/`useMemo`/JSX/Framer Motion/React Query.

## Use when
- Composing UI from Blade/Livewire components.
- Deciding where state lives (Livewire vs Alpine).
- Optimizing render/network cost of a Livewire view.
- Building forms with validation.
- Handling loading, empty, error, and offline states.

---

## Component composition

Compose; do not inherit. Prefer small components with slots over big configurable ones.

```blade
{{-- resources/views/components/card.blade.php (anonymous component) --}}
@props(['variant' => 'default'])
<div {{ $attributes->class(['card', 'card-outlined' => $variant === 'outlined']) }}>
    {{ $slot }}
</div>
```

```blade
<x-card variant="outlined">
    <x-slot:header>Title</x-slot:header>
    Content
</x-card>
```

- **Anonymous components** (view-only, in `resources/views/components`) for pure presentation.
- **Class components** (`app/View/Components`) only when the component needs PHP logic to prepare data.
- **`{{ $attributes }}`** forwards caller classes/attributes — merge, don't overwrite.
- **Named slots** (`<x-slot:header>`) replace prop-drilling content.

### Livewire nesting

A "compound" UI is a parent Livewire component holding child Livewire components. Children are independent; pass data down via props and communicate up via events/listeners — never tight coupling.

```blade
@foreach ($rows as $row)
    <livewire:row-editor :row="$row" :key="$row->id" />
@endforeach
```

Always set `:key` on nested components and loop items so Livewire tracks identity across re-renders.

---

## State placement: Livewire vs Alpine

Put state where it belongs. The wrong choice causes either chattiness or lost server state.

- **Livewire public properties** — server-authoritative data, anything persisted or validated, anything other components react to.
- **Livewire computed properties** (`#[Computed]`) — derived values from properties/DB; cached per request, keeps the view clean.
- **Alpine `x-data`** — purely local UI state that the server never needs: dropdown open/closed, active tab, hover, optimistic toggles.

```blade
{{-- local-only: no server round-trip --}}
<div x-data="{ open: false }">
    <button x-on:click="open = !open">Menu</button>
    <nav x-show="open" x-cloak>…</nav>
</div>
```

```php
// derived server state
#[Computed]
public function total(): int
{
    return $this->items->sum('price');
}
```

Rule of thumb: if toggling it should hit the database or affect validation, it is Livewire; if it is ephemeral chrome, it is Alpine. Bridge the two with `@entangle` only when both sides genuinely need the value.

---

## Performance

- **`wire:key` in loops** — mandatory; without it Livewire mis-reconciles DOM and loses focus/state.
- **Tune `wire:model`** — default is deferred (syncs on action). Use `.live` only when the server must react to every keystroke; prefer `.blur` or `.debounce.500ms` for inputs to cut requests.

```blade
<input type="search" wire:model.live.debounce.400ms="search">
```

- **Lazy / deferred loading** — render expensive components after first paint with `<livewire:report lazy />`, or `#[Lazy]` on the class, to keep the initial response fast.
- **Pagination** — use `WithPagination`; never load full tables into a property.
- **Avoid N+1 in views** — eager-load relations in the query before passing to Blade; cross-reference `@rules/sql/optimalize.mdc`. A relation accessed inside a `@foreach` without eager loading fires one query per row.

```php
$this->orders = Order::with('customer')->latest()->paginate(20);
```

- **Asset stacks** — register component CSS/JS once with `@once` + `@push('scripts')` so repeated components don't duplicate output.

---

## Forms

Prefer Livewire form objects to keep components slim and validation reusable.

```php
// app/Livewire/Forms/MarketForm.php
class MarketForm extends Form
{
    #[Validate('required|string|max:200')]
    public string $name = '';

    #[Validate('required|string')]
    public string $description = '';
}
```

```php
// component
public MarketForm $form;

public function save(CreateMarket $action): void   // Action injected via boot() or method DI
{
    $this->validate();
    $action->handle($this->form->toArray());
    $this->reset('form');
}
```

- **Real-time validation** — `wire:model.blur` plus an `updated()` hook (or per-field `$this->validateOnly($field)`) validates as the user leaves each field without validating the whole form on every key.
- Keep `messages()`/`attributes()` free of identity-revealing detail per `@rules/security/frontend.md`.
- For admin CRUD, prefer Filament forms over hand-built ones.

---

## Loading, empty, error, offline states

Design all four states, not just the happy path.

```blade
{{-- loading --}}
<button wire:click="save" wire:loading.attr="disabled" wire:target="save">
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">Saving…</span>
</button>

{{-- empty --}}
@forelse ($orders as $order)
    <livewire:order-row :order="$order" :key="$order->id" />
@empty
    <x-empty-state title="No orders yet" />
@endforelse

{{-- offline --}}
<div wire:offline class="banner-warning">You are offline — changes will retry.</div>
```

- **Errors** — surface failures with `session()->flash()` + a `role="alert"` region, or a validation message; never swallow exceptions in the component. Delegate the actual work to an Action that can throw, and catch only to present a safe message.
- **Skeletons** — show `wire:loading` placeholders for lazy/deferred components so layout doesn't jump.

---

## Progressive enhancement with Alpine

Render meaningful HTML server-side first; layer Alpine for interactivity so the page is useful before JS runs and degrades gracefully if it doesn't.

```blade
<details x-data x-bind:open="open">  {{-- works without JS via native <details>; Alpine enhances --}}
    <summary>Filters</summary>
    …
</details>
```

Keep Alpine logic small and inline; if it grows beyond a few expressions, move the state into a Livewire component.

---

## Done when
- Components are composed from slots/attributes; no oversized configurable mega-components.
- State sits on the correct layer (Livewire for server/validated, Alpine for local chrome).
- Every loop and nested component has a stable `wire:key`/`:key`.
- `wire:model` strategy minimizes requests; no `.live` where `.blur`/`.debounce` suffices.
- Lists eager-load relations — no N+1 in Blade.
- Forms use form objects + validation; admin CRUD reuses Filament.
- Loading, empty, error, and offline states are all handled.
- No React/Vue artifacts; business logic stays in Actions/Services, not the component.
