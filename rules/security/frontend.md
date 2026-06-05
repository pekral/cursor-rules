---
description: Frontend security rules based on SecureCodeWarrior AI Security Rules. Apply when reviewing or writing client-side code.
alwaysApply: true
---

## Output Handling
- Always prefer `textContent` or `setAttribute` over `innerHTML`, `outerHTML`, or `document.write`.
- Sanitize dynamic content with libraries such as `DOMPurify` before DOM insertion.
- Use Content Security Policy (CSP) headers to restrict script sources and disable unsafe inline scripts.
- Apply strict input validation using allow-lists and well-defined patterns.

## Safe Validation & Error Messages (issue #540)
Frontend forms, toasts, banners, and locale resources surface the same wording the user reads. Apply the same enumeration / introspection / authorization-leak rules as `@rules/security/backend.md` *Safe Validation & Error Messages*, plus these client-side specifics:

- **Mirror the backend wording.** When the server returns *"Invalid credentials."*, the client must render exactly that — do not enrich it with *"check your password"* or *"unknown email"* on the client side. Client-side hints that contradict the safe backend message recreate the enumeration leak.
- **Do not pre-flight existence on the client.** Auth, sign-up, and recovery forms must not call a separate endpoint (e.g. `/api/users/check-email`) before submit to tell the user the address is taken / unknown. The same flow must follow the backend's generic response shape.
- **Never inject attacker input into the message DOM unescaped.** Render rejected values via `textContent` or framework binding, never via `innerHTML`. Reflected validation messages are an XSS vector even when the server already rejected the payload.
- **Strip stack traces and SDK errors before display.** Network failures, schema mismatches, and SDK exception payloads must not be shown verbatim. Replace with a generic *"Something went wrong. Please try again."* and log the detail to the project's error sink — never to `console` on production builds.
- **Translation parity.** Apply the same locale-wide safety walk: a French / Czech / Spanish locale must not reintroduce identity-revealing wording the English source has removed.

## CSS Handling
- Sanitize all user inputs before applying them to style properties.
- Avoid dynamic inline styles where possible.
- Use CSP with style nonces or hashes to validate inline CSS securely.

## Clickjacking Protection
Apply these rules only in production or when generating a standalone application. Disable or relax them during development if you're embedding the app in iframes.
- Use the `Intersection Observer API` to detect UI overlays or clickjacking attempts.
- Add frame-busting logic using JavaScript (`if (top !== self) top.location = self.location`).
- Set `X-Frame-Options` header to `DENY` or use `Content-Security-Policy: frame-ancestors 'none';`
- Use `SameSite` cookie attributes to reduce CSRF exposure across frames.

## Redirects
- Avoid using user input directly in redirects or forwards.
- Use fixed URLs or allow-listed destinations based on internal logic.
- Use URL identifiers (IDs) instead of full paths in parameters.
- Validate redirect URLs to ensure they lead to trusted locations.
- Implement an allowlist for allowed redirections.
- Log all URL redirects for monitoring.
- Use `rel="noopener noreferrer"` for external links to prevent reverse tabnabbing.
