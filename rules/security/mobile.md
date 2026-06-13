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

## WebView Usage
- Limit WebView access to trusted URLs, and disable JavaScript by default.
- Enforce HTTPS in WebView to prevent loading insecure content.
- Regularly clear WebView data (cache, cookies) to reduce the risk of leakage.
- Validate and sanitize input data to prevent malicious scripts from being executed in WebViews.
- Use Content Security Policy (CSP) to restrict the types of resources that can be loaded into WebViews.
