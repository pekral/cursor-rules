---
description: Backend security rules based on SecureCodeWarrior AI Security Rules. Apply when reviewing or writing server-side code.
alwaysApply: true
---

## General Secure Coding Practices
- Validate and sanitize all user inputs to prevent injection attacks.
- Use error handling without revealing sensitive information.
- Avoid exposing sensitive data in API responses.
- Do not hardcode any secrets (credentials, API keys, etc) in the source code or configuration files.

## Safe Validation & Error Messages (issue #540)
Validation messages, exception messages, HTTP error bodies, log lines that reach end users, and every locale entry behind `__()` / `trans()` / `Lang::get()` / `@lang` are an attack surface — careless wording confirms account existence, reveals storage shape, or hints at the next probe. Apply these rules to **every user-facing string the diff adds, modifies, or translates**, in the default locale **and** in every other locale shipped with the project.

- **No identity / account enumeration.** Authentication, password-reset, sign-up, change-email, and any account-lookup endpoint must return one generic message for the whole flow — *"Invalid credentials."*, *"If the account exists, we sent the reset link."*, *"Unable to sign in."* — never a message that distinguishes *email not found* from *wrong password*, *account locked*, *email not verified*, *2FA required*, or *user disabled*. The decision branch must run server-side without leaking into the response body, the response status, the response latency, or the response shape (no field-level error on `email` only, no different envelope per branch).
- **No authorization granularity leaks.** Permission-denied responses must not reveal whether the resource exists. Return a generic `404 Not Found` (or the project's equivalent generic error) for both *missing* and *forbidden*; never *"You do not have permission to access invoice #42"* (confirms the row exists) or *"Project not found in your workspace"* (confirms it exists in another).
- **No internal implementation detail.** Do not surface stack traces, file paths, framework or library versions, fully-qualified class names, database table / column / index names, SQL fragments, ORM error envelopes, queue / cache driver identifiers, or feature-flag keys to end users. Strip them before the response leaves the application boundary; log the detail server-side instead.
- **No verbatim echo of attacker input.** Validation messages must not concatenate the rejected value into the response — *"Email 'admin@example.com' is invalid"* is reflective and aids phishing / XSS pivots. Reference the field by name only — *"The email address is invalid."* — and let the client display the user's input next to the message if needed.
- **No password / token policy leak beyond the stated rule.** Reject reasons must match the rule the user can read in the form ("Password must be at least 12 characters."). Do not reveal proximity to the rule ("only one character short", "almost matches the breach list", "this password was used 11 months ago"), do not state which character class is missing in a single response when multiple are missing (collapse into the stated requirement), and do not echo password fragments back.
- **No timing or shape side channels.** Generic messages lose their value when the response latency or the response body shape differs per branch. Equalize the server-side path before responding (constant-time compare on credential checks, identical envelope for *success / not found / forbidden* on enumeration-sensitive endpoints).
- **Translations carry the same contract.** When the diff adds a key in the default locale, **every other shipped locale must receive the same safe wording** — a translator must not reintroduce *"Account does not exist"* in `cs.json` after the English source said *"Invalid credentials."*. The CR translation-completeness walk owns missing-key findings; this rule owns wording findings on every locale that already carries the key.
- **Specificity stays on the safe surfaces.** Schema-level validation that does **not** leak existence — *"The age must be at least 18."*, *"Phone number is required."*, *"File must be a PDF under 5 MB."* — stays specific so users can fix their input; the above prohibitions cover the enumeration / authorization / introspection surfaces only.

## HTTP Security headers and Cookies
- Use a Content Security Policy (CSP) to protect against XSS and clickjacking attacks.
- Set cookies with `HttpOnly`, `Secure`, and `SameSite` attributes.
- Enforce strict CORS policies for cookies.

### CSRF Protection
Apply the following rules only if authentication relies on cookies instead of tokens.
- Use anti-CSRF tokens for state-changing operations.
- Validate `Origin` and `Referer` headers for non-GET requests.
- Require re-authentication before performing sensitive actions.

## Output Rendering
- Ensure output is encoded correctly for the corresponding context.
- Escape special characters in output to prevent injection attacks.

## Database
- Use parameterized queries or ORM to prevent injections.
- Implement proper authentication and authorization.
- Handle sensitive data properly.
- Monitor security issues.
- Apply the principle of least privilege to database users.

## API Security
- Apply authentication and integrity checks on all API requests.
- Configure CORS policies to restrict cross-origin access to trusted domains only.
- Apply rate limiting to manage traffic.
- Enforce security headers.
- Handle errors securely without revealing sensitive details to end users.
- Log access and actions for monitoring, auditing, and detecting abnormal activity.

## External Requests
- Restrict outbound requests to only necessary external services and internal endpoints.
- Use allowlists to define permitted destinations instead of blocking known bad domains.
- Disable unnecessary URL fetching capabilities in your application.
- Validate and sanitize all user-supplied URLs before making requests.
- Implement request timeouts and rate limits to prevent abuse.

## Malicious Code & Supply-Chain Indicators (issue #549)
Code that fetches remote content, executes processes, or handles transport security can hide attacker behaviour behind ordinary-looking flags. Treat the patterns below as high-signal indicators of malicious or compromised code and flag every match on a line the diff adds or modifies — in application code (`exec()`, `shell_exec()`, `system()`, `proc_open()`, backticks, `Process::run()`), in shell / deploy / CI scripts (`*.sh`, `Makefile`, `composer.json` / `package.json` script hooks, GitHub Actions steps), and in installer / post-install hooks. Each pattern is a finding unless the surrounding code documents an explicit, legitimate reason inline.

- **Silent remote fetch ("tichý curl").** `curl` / `wget` invoked with silence flags (`-s`, `-sS`, `--silent`, `-q`, `wget -q`), especially when the response is piped straight into an interpreter (`curl … | sh`, `curl … | bash`, `| php`, `| python`) or written to disk and executed. A silent download-and-execute is a remote-code-execution and supply-chain vector: the URL is attacker-controllable and the transfer leaves no trace in logs. Require the destination to be an allow-listed, version-pinned HTTPS URL with a verified checksum / signature, and never pipe a network response directly into a shell. Severity: **Critical** when piped to an interpreter or executed; **High** otherwise.
- **Disabled TLS validation ("ignorování TLS validace").** Any code or flag that turns off certificate or hostname verification: `curl -k` / `--insecure`, `wget --no-check-certificate`, PHP `CURLOPT_SSL_VERIFYPEER => false` / `CURLOPT_SSL_VERIFYHOST => 0`, Guzzle / HTTP-client `'verify' => false`, a `stream_context` with `verify_peer => false` / `verify_peer_name => false`, or `NODE_TLS_REJECT_UNAUTHORIZED=0`. Disabling TLS validation exposes every request to man-in-the-middle interception and payload tampering. Keep validation on; if a self-signed internal CA is genuinely required, pin its certificate bundle (`CURLOPT_CAINFO`, `'verify' => '/path/to/ca.pem'`) instead of disabling the check. Severity: **Critical** on any request carrying credentials, tokens, or downloaded executables; **High** otherwise.
- **Suppressed error output ("potlačení chybového výstupu").** Constructs that hide failures from logs and reviewers: shell redirects `2>/dev/null` / `&>/dev/null` / `2>&1 >/dev/null` on a command whose result matters; the PHP error-suppression operator `@` on a security-relevant call (`@file_get_contents`, `@unlink`, `@exec`); `error_reporting(0)` / `ini_set('display_errors', '0')` flipped inside application logic; and empty `catch {}` blocks that swallow an exception without logging or rethrowing. Suppressed output lets a failed integrity check, a rejected TLS handshake, or a failed command pass unnoticed. Require the error to be handled or logged through the project's error sink; never silence a security-relevant operation. Severity: **High**; **Critical** when the suppressed call performs a download, executes a process, or validates a signature / checksum.
- **Hidden file + detached background process ("skrytý soubor v /tmp a spuštění procesu na pozadí").** Writing to a world-writable or hidden path — `/tmp`, `/var/tmp`, `/dev/shm`, or any dot-prefixed filename (`/tmp/.x`) — combined with launching a detached or backgrounded process: a trailing `&`, `nohup … &`, `disown`, `setsid`, `at` / `cron` registration, `proc_open` / `exec` with `> /dev/null 2>&1 &`, or `register_shutdown_function` spawning a process. This is the classic dropper-plus-persistence pattern. Require temp files to use `tmpfile()` / `tempnam(sys_get_temp_dir(), …)` with `0600` permissions and deletion in a `finally`, and route background work through the project's queue / scheduler — never a raw detached shell process. Severity: **Critical** when both halves co-occur (hidden / temp write **and** background execution); **High** for either half alone on a security-relevant path.

Each finding maps to an active attack technique (RCE, MITM, evasion, persistence), so the **Suggested Fix** replaces the unsafe construct with the allow-listed / verified / logged / queued equivalent above. Genuinely benign uses — a `-s` curl inside a documentation example, a `2>/dev/null` on a best-effort cleanup annotated with a comment — are not findings when the intent is documented inline.
