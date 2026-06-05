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
