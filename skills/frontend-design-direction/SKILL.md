---
name: frontend-design-direction
description: "Use when the work is not just making UI function but making it feel purposeful and polished — choosing a deliberate design direction for a Laravel/Blade/Livewire/Filament interface."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/laravel/filament.mdc` — for custom Blade + Tailwind, create and register a custom theme; use Heroicon, not raw icon strings.
- Apply `@rules/laravel/livewire.mdc` — keep Blade presentation-only; reuse existing Livewire/Blade patterns before inventing new ones.
- Apply `@rules/security/frontend.md` only if the direction introduces dynamic output, inline styles, or CSP-sensitive markup — sanitize before DOM insertion and prefer safe rendering.
- Stack is Blade + Livewire + Alpine.js + Filament + Tailwind. No React/Vue/Next.

## Use when
- The user asks to build a page, app, dashboard, component, landing page, or UI.
- The user asks to make an interface more polished, distinctive, or less generic.
- The work needs visual hierarchy, typography, color, motion, and layout decisions.
- The current UI works but reads as flat, templated, or mismatched to its audience.

This skill is about judgment, not mechanics. Pair it with `design-system` (tokens/audit), `frontend-patterns` (implementation), and `frontend-a11y` (accessibility).

---

## Choose a direction before coding

Decide these five things explicitly first:

1. **Purpose** — what job does this interface do?
2. **Audience** — who repeats this workflow, and what must they scan first?
3. **Tone** — name it: utilitarian, editorial, playful, industrial, refined, technical, maximal, minimal, dense, or calm.
4. **Memorable detail** — one deliberate idea that makes the result feel intentional rather than generated.
5. **Constraints** — framework, accessibility, performance, responsiveness, and the existing design system.

Match the direction to the domain. A SaaS operations tool — including most Filament panels — should usually be dense, quiet, and scannable. A portfolio, launch page, or editorial piece can be expressive. Do not force a marketing-landing composition onto a tool meant for repeated daily use.

---

## Implementation guidance

- Build the actual usable experience as the first screen unless the user explicitly asks for marketing copy.
- **Reuse what exists first** — existing Blade components (`<x-…>`), Tailwind design tokens, the Filament theme, Heroicon, and established Livewire patterns — before introducing a new visual system. New components should consume current tokens, not hard-coded values.
- Use real or generated assets when the interface depends on images, charts, products, or inspectable media.
- Prefer contextual typography and spacing over generic oversized hero text.
- Keep palettes multi-dimensional — avoid a UI dominated by one hue family. Drive color from Tailwind tokens / CSS variables so the direction stays coherent across light/dark and component states.
- Design responsive constraints explicitly: grids, aspect ratios, min/max sizes, and stable toolbars should not shift when labels or hover states appear.
- Use motion sparingly and deliberately — prefer high-signal Alpine transitions and `wire:loading` feedback that clarify state over decorative animation, and respect `motion-reduce:`.
- Verify text fit on mobile and desktop; long labels must wrap or resize, not overflow.

---

## Anti-patterns

- Default generated patterns: purple gradients, decorative blobs, oversized cards, vague hero copy, stock-like atmospheric media.
- Cards nested inside cards.
- One decorative style applied everywhere when the domain calls for restraint.
- Hiding the primary tool, object, or workflow behind generic marketing sections.
- Adding a new front-end dependency for a single flourish that doesn't pay for itself — stay within Blade/Alpine/Tailwind/Filament.
- Describing the UI's features inside the UI when the controls can speak for themselves.
- Overriding the Filament theme inline instead of extending the registered theme.

---

## Review checklist

- The first viewport immediately communicates the product, workflow, or object.
- Visual hierarchy supports scanning and repeated use.
- Typography fits its container and does not overlap adjacent content.
- Color choices have contrast and do not collapse into a one-note palette.
- Heroicons are used for familiar tool actions where available.
- Responsive layout has stable dimensions for grids, toolbars, controls, and tiles.
- Assets render and carry the subject matter instead of acting as filler.
- Motion improves orientation and does not mask sluggishness; reduced-motion is respected.
- The result reuses existing Blade components, tokens, and the Filament theme — departing only with a clear reason.
