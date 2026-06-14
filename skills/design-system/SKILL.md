---
name: design-system
description: "Use when generating, auditing, or reviewing the visual design system of a Laravel app — Tailwind tokens, Filament theming, Blade/Livewire component consistency, and visual-polish audits."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/laravel/filament.mdc` — for custom Blade + Tailwind, create and register a custom theme; use `Filament\Support\Icons\Heroicon` for icons.
- Apply `@rules/laravel/livewire.mdc` — keep Blade templates presentation-only; components are slim entry points.
- Apply `@rules/laravel/architecture.mdc` and `@rules/laravel/laravel.mdc` for file placement.
- Apply `@rules/security/frontend.md` if any audit fix touches output rendering or CSP.
- Stack is Blade + Livewire + Alpine.js + Filament + Tailwind. No React/Vue/Next.
- Any live-URL or browser-screenshot step is OPTIONAL and tool-agnostic — never a hard dependency.

## Use when
- Starting a project that needs a coherent design system.
- Auditing an existing codebase for visual consistency.
- Before a redesign — to understand what already exists.
- The UI looks "off" but the cause is unclear.
- Reviewing a PR that touches styling, Tailwind config, or a Filament theme.

This skill has three modes. Pick the one that matches the request.

---

## Mode 1: Generate a design system

Produce a documented token set wired into Tailwind, a Filament theme, and reusable Blade/Livewire components.

### Steps
1. **Scan** existing styling for de-facto patterns: `tailwind.config.js`, `resources/css/app.css`, Blade views under `resources/views`, and any Filament theme CSS. Collect every color, font, size, radius, and shadow already in use.
2. **Extract tokens** into these groups: colors (brand, neutral, semantic success/warning/danger/info), typography (font families, type scale, weights, line heights), spacing scale, border radius, shadows, breakpoints, z-index layers.
3. **Inspiration (optional)** — if a browser tool is available you may reference real sites; never depend on it. Tokens must be derivable from the codebase alone.
4. **Define tokens** in Tailwind config plus CSS custom properties so values stay coherent across light/dark and component states.
5. **Theme Filament** — register a custom panel theme that consumes the same variables.
6. **Build reusable components** as anonymous Blade components (and Livewire components where interactive state is needed) that consume the tokens — never hard-coded hex.
7. **Document** decisions and rationale in `DESIGN.md`.

### Tailwind tokens + CSS variables

```js
// tailwind.config.js
export default {
  theme: {
    extend: {
      colors: {
        brand: { DEFAULT: 'rgb(var(--c-brand) / <alpha-value>)' },
        surface: 'rgb(var(--c-surface) / <alpha-value>)',
        danger: 'rgb(var(--c-danger) / <alpha-value>)',
      },
      borderRadius: { card: 'var(--radius-card)' },
      boxShadow: { card: 'var(--shadow-card)' },
    },
  },
};
```

```css
/* resources/css/app.css */
:root {
  --c-brand: 37 99 235;      /* single source of truth */
  --c-surface: 255 255 255;
  --c-danger: 220 38 38;
  --radius-card: 0.5rem;
  --shadow-card: 0 1px 2px rgb(0 0 0 / 0.06);
}
.dark {
  --c-surface: 17 24 39;
}
```

### Reusable Blade component

```blade
{{-- resources/views/components/ui/card.blade.php (anonymous) --}}
@props(['variant' => 'default'])
<div {{ $attributes->class([
    'rounded-card shadow-card bg-surface',
    'border border-gray-200 dark:border-gray-700' => $variant === 'outlined',
]) }}>
    {{ $slot }}
</div>
```

Use `<x-ui.card>…</x-ui.card>` everywhere instead of repeating utility strings. For interactive widgets, wrap state in a Livewire or Alpine component but keep the visual shell in the shared Blade component.

### Filament theme

```css
/* resources/css/filament/admin/theme.css */
@import '/vendor/filament/filament/resources/css/theme.css';
@import '../../app.css'; /* reuse the same --c-* variables */
```

Register it on the panel provider with `->viteTheme(...)` so Filament and the public UI share one token set.

**Output:** `DESIGN.md` (token tables + rationale), updated `tailwind.config.js`, CSS variables in `app.css`, the Filament theme, and the shared Blade/Livewire components.

---

## Mode 2: Visual audit

Score the UI across 10 dimensions, 0–10 each. Every dimension needs a score, a concrete `file:line` example, and a fix.

1. **Color consistency** — palette tokens vs ad-hoc hex/`rgb()` strings in Blade.
2. **Typographic hierarchy** — clear `h1 > h2 > h3 > body > caption`; no skipped levels.
3. **Spacing rhythm** — a consistent scale (4/8/16) vs arbitrary `mt-[13px]`.
4. **Component consistency** — similar elements built from the same `<x-ui.*>` component.
5. **Responsive behavior** — fluid across breakpoints; no overflow or layout breaks.
6. **Dark mode** — complete `dark:` coverage, not half-applied.
7. **Motion** — purposeful Alpine/`transition` use vs gratuitous animation.
8. **Accessibility / contrast** — token contrast ratios, visible focus states, touch targets (cross-link `frontend-a11y`).
9. **Information density** — clean and scannable vs cluttered.
10. **Polish** — hover, transition, loading (`wire:loading`), and empty states present.

A live-URL crawl or screenshot pass is OPTIONAL. If no browser tool exists, audit from the Blade/Tailwind source and Filament config directly — that is sufficient.

**Report format per dimension:**
```
Color consistency — 6/10
  resources/views/livewire/dashboard.blade.php:42 — bg-[#3b82f6] bypasses the brand token
  Fix: replace with bg-brand and add the token if missing
```

---

## Mode 3: AI-slop detection

Flag generic AI-generated design tells and propose a deliberate alternative:

- Gratuitous gradients on every surface.
- Purple-to-blue default gradients with no brand basis.
- Glassmorphism / backdrop-blur cards with no functional purpose.
- Rounded corners on elements that should be square (tables, inputs in dense UIs).
- Excessive scroll-triggered animation.
- Generic centered-hero over a stock atmospheric gradient.
- A personality-free sans stack that ignores the product domain.

For each hit, give `file:line` and a concrete fix that ties back to the project tokens and the chosen design direction (cross-link `frontend-design-direction`).

---

## Done when
- (Generate) Tokens live in Tailwind config + CSS variables, the Filament theme consumes them, shared Blade/Livewire components replace duplicated utility strings, and `DESIGN.md` documents the rationale.
- (Audit) All 10 dimensions scored, each with a `file:line` example and a fix.
- (Slop) Every flagged pattern has a location and a deliberate replacement.
- No hard-coded colors remain where a token exists; no React/Vue artifacts introduced.
