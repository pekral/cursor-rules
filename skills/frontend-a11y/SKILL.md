---
name: frontend-a11y
description: "Use when building or reviewing accessible UI in a Laravel app — semantic Blade markup, accessible forms, keyboard navigation with Alpine, focus and live-region management for Livewire updates, contrast, and Filament accessibility."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/laravel/livewire.mdc` — keep Blade presentation-only; use `wire:model` for bindings; components stay slim.
- Apply `@rules/laravel/filament.mdc` — reuse Filament's built-in accessible form/action components rather than rebuilding them.
- Apply `@rules/security/frontend.md` — sanitize any dynamic content before DOM insertion; prefer `textContent`/`setAttribute` over `innerHTML`.
- Stack is Blade + Livewire + Alpine.js + Filament + Tailwind. No React/Vue/Next — never output `useState`/`useEffect`/`useRef`/JSX.

## Use when
- Building or reviewing forms (`<input>`, `<select>`, `<textarea>`).
- Creating interactive elements (modals, dropdowns, tabs, tooltips) with Alpine.
- Adding `<div>`/`<span>` with `wire:click` or `x-on:click` instead of a button.
- Adding any `aria-*` attribute.
- Managing focus or announcing async updates after a Livewire round-trip.
- Acting on accessibility feedback from a review tool.

---

## Form accessibility

Missing `for`/`id` pairing and disconnected error messages are the most commonly flagged issues. Livewire's `@error` directive pairs naturally with `aria-describedby`.

### Label connection

```blade
{{-- BAD: no association --}}
<label>Email</label>
<input type="email" wire:model="email">

{{-- GOOD: for matches id --}}
<label for="email">Email</label>
<input id="email" type="email" wire:model="email">
```

### Required fields

```blade
{{-- visual asterisk hidden from SR; required + aria-required convey state --}}
<label for="email">Email <span aria-hidden="true">*</span></label>
<input id="email" type="email" required aria-required="true" wire:model="email">
```

### Error association with Livewire

```blade
<label for="email">Email <span aria-hidden="true">*</span></label>
<input
    id="email"
    type="email"
    wire:model.blur="email"
    autocomplete="email"
    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
>
@error('email')
    <span id="email-error" role="alert" class="text-danger">{{ $message }}</span>
@enderror
```

`@error` controls both the `aria-invalid`/`aria-describedby` wiring and the message, so the link is always consistent with the validation state. Use `fieldset`/`legend` to group related controls (radio sets, address blocks).

**Filament forms** already emit connected labels, `aria-describedby` error wiring, and required markers. Reuse Filament form components instead of hand-rolling markup; do not strip their generated attributes.

---

## Semantic HTML

Use the element that matches intent. Screen readers and keyboard users depend on native semantics.

```blade
{{-- BAD: div has no role, no keyboard support --}}
<div wire:click="save">Submit</div>

{{-- GOOD: button is focusable, fires on Enter/Space, announced as "button" --}}
<button type="button" wire:click="save">Submit</button>

{{-- BAD: fake navigation --}}
<div wire:click="goHome">Home</div>
{{-- GOOD: real anchor — supports middle-click, right-click, keyboard --}}
<a href="{{ route('home') }}">Home</a>
```

Keep heading levels sequential (`h1 → h2 → h3`); never skip a level for styling.

---

## ARIA — only when native HTML is insufficient

Wrong ARIA is worse than none.

```blade
{{-- aria-label: when no visible text exists --}}
<button type="button" aria-label="Close" wire:click="close">
    <x-heroicon-o-x-mark aria-hidden="true" class="h-5 w-5" />
</button>

{{-- aria-labelledby: when a visible heading exists --}}
<section aria-labelledby="orders-title">
    <h2 id="orders-title">Recent Orders</h2>
</section>
```

Expandable trigger:

```blade
<button type="button" aria-expanded="false" aria-controls="panel"
        x-data x-on:click="$el.setAttribute('aria-expanded', $el.getAttribute('aria-expanded') === 'true' ? 'false' : 'true')">
    Details
</button>
<div id="panel" x-show="open" hidden>…</div>
```

---

## Keyboard navigation with Alpine

Every interactive element must be reachable and operable by keyboard alone. Build custom widgets with Alpine event modifiers.

```blade
<div x-data="{ open: false, active: 0, options: @js($options) }"
     role="combobox" :aria-expanded="open" aria-haspopup="listbox" tabindex="0"
     x-on:keydown.arrow-down.prevent="active = Math.min(active + 1, options.length - 1)"
     x-on:keydown.arrow-up.prevent="active = Math.max(active - 1, 0)"
     x-on:keydown.enter.prevent="$wire.select(options[active]); open = false"
     x-on:keydown.escape="open = false"
     x-on:click="open = !open">
    <span x-text="options[active]"></span>
    <ul x-show="open" role="listbox">
        <template x-for="(opt, i) in options" :key="opt">
            <li role="option" :aria-selected="i === active" x-text="opt"
                x-on:click="$wire.select(opt); open = false"></li>
        </template>
    </ul>
</div>
```

### Modal / dialog with focus trap

```blade
<div x-data="{ open: @entangle('showModal') }" x-show="open" x-cloak
     role="dialog" aria-modal="true" aria-labelledby="modal-title"
     x-on:keydown.escape.window="open = false"
     x-trap.noscroll="open"
     {{-- x-trap (Alpine focus plugin) cycles Tab/Shift+Tab inside and restores focus on close --}}
     tabindex="-1">
    <h2 id="modal-title">{{ $title }}</h2>
    {{ $slot }}
    <button type="button" wire:click="$set('showModal', false)">Close</button>
</div>
```

`x-trap` from the official Alpine Focus plugin handles the trap and restoring focus to the opener. If the plugin is not installed, save `document.activeElement` on open and call `.focus()` on it when closing.

---

## Focus & live regions for Livewire updates

A Livewire request swaps DOM fragments; focus and announcements must survive it.

- **Loading feedback:** `wire:loading` / `wire:target` toggle visible state without script.
- **Announce async results** with a polite live region so screen readers hear them:

```blade
<div aria-live="polite" aria-atomic="true" class="sr-only">
    <span wire:loading wire:target="save">Saving…</span>
</div>
@if (session('status'))
    <div role="status" aria-live="polite">{{ session('status') }}</div>
@endif
```

- **Urgent errors** use `aria-live="assertive"` (or `role="alert"`), nothing else.
- **Return focus after a modal closes** — dispatch a Livewire/Alpine event and `.focus()` the trigger, or rely on `x-trap`.
- **Preserve focus across re-render** — give moving elements a stable `wire:key`; without it Livewire may discard the focused node.
- **Disable controls during requests** with `wire:loading.attr="disabled"` so double-submits are blocked accessibly.

---

## Images, icons, contrast, motion

```blade
<img src="/decoration.png" alt="" aria-hidden="true">                {{-- decorative --}}
<img src="/chart.png" alt="Revenue rose 23% from Jan to Mar">       {{-- meaningful --}}
<button type="button" aria-label="Delete"><x-heroicon-o-trash aria-hidden="true" /></button>
```

- **Contrast:** ensure Tailwind text/background token pairs meet WCAG AA (4.5:1 body, 3:1 large). Verify both light and `dark:` variants. Never rely on color alone to convey state — pair with text or an icon.
- **Reduced motion:** gate animation with Tailwind's `motion-safe:`/`motion-reduce:` variants instead of always-on transitions.

```blade
<div class="transition-transform motion-reduce:transition-none">…</div>
```

For Alpine transitions, branch on the media query:

```blade
<div x-data="{ reduce: window.matchMedia('(prefers-reduced-motion: reduce)').matches }"
     x-transition:enter="reduce ? '' : 'transition duration-300'">…</div>
```

---

## Anti-patterns

```blade
<div wire:click="save">Save</div>                  {{-- non-interactive element with handler --}}
<div aria-label="Navigation">…</div>               {{-- aria-label on element with no role --}}
<input placeholder="Email">                        {{-- placeholder used instead of label --}}
<button tabindex="3">Submit</button>               {{-- positive tabindex breaks tab order --}}
<button aria-hidden="true">Open</button>           {{-- aria-hidden on a focusable element --}}
```

---

## Checklist

- [ ] Every `<input>`/`<select>`/`<textarea>` has a connected `<label for>`.
- [ ] Errors link via `@error` → `aria-describedby` and carry `role="alert"`.
- [ ] No `wire:click`/`x-on:click` on a `<div>`/`<span>` without role, `tabindex`, and key handling.
- [ ] Icon-only buttons have `aria-label`; their icons are `aria-hidden`.
- [ ] Decorative images use `alt=""` and `aria-hidden="true"`.
- [ ] Modals trap focus and restore it on close (`x-trap` or manual save/restore).
- [ ] Async Livewire results announce via `aria-live`; controls disable during requests.
- [ ] Moving elements have a stable `wire:key` so focus survives re-render.
- [ ] Token color pairs meet AA contrast in light and dark; state is not color-only.
- [ ] Animations respect `motion-reduce:` / `prefers-reduced-motion`.
- [ ] Filament's generated accessibility attributes are left intact.
