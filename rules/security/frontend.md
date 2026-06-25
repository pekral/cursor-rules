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

## Malicious Code & Supply-Chain Indicators (issue #549)
Client-side, build-tooling, and Node / Electron code carry the same attacker indicators as the backend. Apply `@rules/security/backend.md` *Malicious Code & Supply-Chain Indicators*, with these client-side specifics:

- **Disabled TLS validation.** `NODE_TLS_REJECT_UNAUTHORIZED=0`, `rejectUnauthorized: false` on an `https` / `fetch` / `axios` agent, Electron `webPreferences` that ignore certificate errors, or a `certificate-error` handler / `setCertificateVerifyProc` that trusts unconditionally. Keep verification on and pin an internal CA bundle instead of disabling it.
- **Silent remote fetch piped to execution.** A build or `postinstall` script (`package.json` `scripts`) running `curl -s … | sh` or `node -e "$(curl …)"` pulls unverified code into the install. Require pinned, checksum-verified sources.
- **Swallowed errors hiding network calls.** Empty `.catch(() => {})` / `try {} catch {}` around a `fetch` / `XMLHttpRequest` that beacons or exfiltrates data hides both the failure and the call; surface and log the error, and justify any outbound call against the allow-list.

Severity follows the backend rule. Browser sandboxes cannot disable TLS, so this clause targets Node / Electron / build-tooling code in the frontend tree.

## Malicious File Upload Content (issue #680)
Apply the same CONTENT / RENDER rules as `@rules/security/backend.md` *Malicious File Upload Content*, with these client-side specifics:

> **Scope boundary — CONTENT / RENDER only.** This rule covers the CONTENT / RENDER surface of an uploaded file (active content executed when the file's bytes, name, or metadata are later rendered or served). The file TYPE / TRANSPORT surface (extension allow-list, declared-vs-actual MIME, magic-byte signature, double extension, path traversal, executability in webroot) stays with `security-review/SKILL.md` File Handling — **raise one finding per violation, never both**. A single upload sink that fails both surfaces produces the type/transport finding for the accept decision and the content/render finding for the output decision, on distinct lines; never two findings for the same line.

- **Never use `innerHTML` for filenames or file content.** Filenames, EXIF fields, and any other user-controlled upload metadata must be inserted into the DOM via `textContent` or a framework binding — never via `innerHTML`, `outerHTML`, `dangerouslySetInnerHTML`, or `v-html`. Use `DOMPurify.sanitize()` before any insertion into a rich-text or HTML context.
- **SVG uploads must not be rendered inline.** Never embed a user-uploaded SVG directly as inline HTML or via `<embed>` / `<object>` — these give the SVG a document origin and allow `<script>` and `on*` event handlers to execute. Load SVG previews exclusively via `<img src="...">` (which blocks script execution) and only after the backend has served it with `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff`.
- **Do not trust the client-supplied MIME type.** `File.type` in the browser reflects what the OS / browser reports — it is attacker-controllable. Never use it as the sole gate for accepting or rendering a file. Always defer to the backend's server-side MIME and magic-byte validation result.
- **Previewing file content in the browser.** When rendering a preview (text, image, PDF) before upload, use a sandboxed `<iframe sandbox>` or a Blob URL with a strict Content-Type; never inject raw file bytes into the main document's DOM.

## Hidden / Invisible Characters in Stored Fields (issue #714)
Apply the same INPUT / STORAGE rules as `@rules/security/backend.md` *Hidden / Invisible Characters in Stored Fields* — the durable defense is server-side NFC normalization and invisible / bidi / control-character stripping at the write boundary, before the value reaches the database. Client-side specifics:

- **Never rely on client-side stripping as the security control.** Trimming zero-width or bidi characters in JavaScript before submit is a UX nicety only; the attacker controls the request and can submit the raw bytes directly. The authoritative sanitization is the backend write-boundary rule — defer to it.
- **Render stored values defensively.** When a value that may contain bidirectional control characters is displayed (a username, comment, filename), isolate it with `unicode-bidi: isolate` / `<bdi>` or strip the bidi range at render so a persisted Trojan-Source override cannot spoof the surrounding UI. This is render-side defense in depth; it does not replace cleaning the stored bytes.
- **Do not inject user-controlled strings into the DOM unescaped** (see *Output Handling* above) — hidden-character stripping is about the stored value, output escaping is a separate, still-mandatory control.

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
