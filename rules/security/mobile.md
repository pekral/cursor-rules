---
description: Mobile security rules based on SecureCodeWarrior AI Security Rules. Apply when reviewing or writing mobile application code.
alwaysApply: true
---

## General Secure Coding Practices
- Validate and sanitize all user inputs to prevent injection attacks.
- Use error handling without revealing sensitive information.
- Avoid exposing sensitive data in API responses.
- Do not hardcode any secrets (credentials, API keys, etc) in the source code or configuration files.
- Use parameterized queries or prepared statements when performing database queries.

## Safe Validation & Error Messages (issue #540)
Apply the same enumeration / introspection / authorization-leak rules as `@rules/security/backend.md` *Safe Validation & Error Messages*, plus these mobile-specific clauses:

- **No native crash dialogs surfaced to the user.** Uncaught exceptions, native crash reasons, and SDK error codes must not reach end-user UI. Catch at the screen boundary, render a generic *"Something went wrong. Please try again."*, and forward the detail to the project's crash reporter.
- **WebView error pages must stay generic.** When a WebView fails to load, render the app's own fallback view — do not pass through the WebView's default page that may expose the failing URL, the underlying network error code, or the local file path probed.
- **Logs / debug overlays are not user-facing channels.** Verbose authentication or API-error logging is allowed only when the build flag (debug / staging) explicitly enables it; production builds must strip the detail from any surface the end user can read (Logcat over USB does not count, in-app debug menus do).
- **Translation parity.** Every locale shipped in the app bundle (`strings.xml`, `Localizable.strings`, JSON locale assets) carries the same safe wording — a localizer must not reintroduce *"Email not registered"* in one locale after the source removed it.

## Malicious Code & Supply-Chain Indicators (issue #549)
Apply `@rules/security/backend.md` *Malicious Code & Supply-Chain Indicators*, with these mobile-specific clauses:

- **Disabled TLS / certificate validation.** A trust-all `TrustManager` / `X509TrustManager` with an empty `checkServerTrusted`, a `HostnameVerifier` returning `true`, an iOS `URLSession` delegate accepting any server trust, `NSAllowsArbitraryLoads = true` in ATS, or disabled certificate pinning. Keep validation on and pin the production certificate / public-key set.
- **Silent download + background execution.** Fetching a payload over an unlogged channel and running it on a background thread / `WorkManager` / `BGTaskScheduler` job, or writing it to app cache / external storage before loading. Require signed, version-pinned assets and the platform's job scheduler — never dynamically loaded remote code.
- **Suppressed errors on security operations.** An empty `catch {}` around a network, keystore, or pinning call hides a failed handshake or tamper check; surface and report it through the crash reporter.

Severity follows the backend rule.

## Malicious File Upload Content (issue #680)
Apply the same CONTENT / RENDER rules as `@rules/security/backend.md` *Malicious File Upload Content*, with these mobile-specific specifics:

> **Scope boundary — CONTENT / RENDER only.** This rule covers the CONTENT / RENDER surface of an uploaded file (active content executed when the file's bytes, name, or metadata are later rendered or served). The file TYPE / TRANSPORT surface (extension allow-list, declared-vs-actual MIME, magic-byte signature, double extension, path traversal, executability in webroot) stays with `security-review/SKILL.md` File Handling — **raise one finding per violation, never both**. A single upload sink that fails both surfaces produces the type/transport finding for the accept decision and the content/render finding for the output decision, on distinct lines; never two findings for the same line.

- **WebView must not render user-uploaded HTML or SVG without sanitization.** Loading a user-uploaded HTML or SVG file into a WebView gives it script execution capability. Always sanitize HTML/SVG content server-side before delivery and render it in a sandboxed WebView (`UIWebView` / `WKWebView` with a restrictive CSP, or `WebView` with `setJavaScriptEnabled(false)`) or as a static preview image rather than an interactive document.
- **Shared / opened files must be validated.** Files opened via the OS share sheet, document picker, or deep link (iOS `UIDocumentPickerViewController`, Android `Intent.ACTION_OPEN_DOCUMENT`) arrive as attacker-controlled input. Validate the file's MIME and magic bytes server-side and treat filename and metadata fields as untrusted strings — escape or sanitize before displaying in UI.
- **Do not render filenames or metadata into HTML contexts.** If the app embeds file metadata (name, EXIF tags, PDF author) into HTML rendered inside a WebView, escape all values as HTML entities; never concatenate raw strings into HTML.

## Hidden / Invisible Characters in Stored Fields (issue #714)
Apply the same INPUT / STORAGE rules as `@rules/security/backend.md` *Hidden / Invisible Characters in Stored Fields* — the durable defense is server-side NFC normalization and invisible / bidi / control-character stripping at the write boundary, before the value reaches the database or the backend API. Mobile-specific specifics:

- **Sanitize on the server, not only in the app.** Stripping zero-width / bidi / control characters in the client before the API call is a UX nicety; a tampered or repackaged app, or a direct API call, submits the raw bytes. The backend write-boundary rule is the authoritative control.
- **Isolate bidirectional text on render.** When displaying a stored value that may carry bidi control characters (username, comment, filename) in a native label or `WKWebView` / Android `WebView`, isolate it (`<bdi>` / `unicode-bidi: isolate` in WebView, or strip the bidi range) so a persisted Trojan-Source override cannot spoof the surrounding UI.
- **Treat shared / deep-link strings as untrusted** (see *Malicious File Upload Content* above) — filenames and metadata arriving via the share sheet or a deep link can carry the same invisible characters; normalize and strip them server-side before persisting.

## WebView Usage
- Limit WebView access to trusted URLs, and disable JavaScript by default.
- Enforce HTTPS in WebView to prevent loading insecure content.
- Regularly clear WebView data (cache, cookies) to reduce the risk of leakage.
- Validate and sanitize input data to prevent malicious scripts from being executed in WebViews.
- Use Content Security Policy (CSP) to restrict the types of resources that can be loaded into WebViews.
