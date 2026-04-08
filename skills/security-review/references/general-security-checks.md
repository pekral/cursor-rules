# General Security Checks

## Input Validation and Sanitization
- All user input is validated and sanitized before processing.
- Parameterized queries or ORM used for all database queries (no string concatenation with user input).

## Injection Vulnerabilities
- SQL injection
- Command injection
- LDAP injection
- XSS (reflected, stored, DOM-based)

## Authentication and Authorization
- Authentication checks on all protected endpoints.
- Authorization checks enforce least privilege.
- Session management follows secure defaults.

## Sensitive Data Exposure
- No hardcoded secrets (credentials, API keys, tokens) in source code or configuration.
- Error handling does not reveal sensitive information (stack traces, internal paths, DB details).
- API responses do not expose unnecessary sensitive data.

## HTTP Security Controls
- CSP (Content Security Policy) headers configured.
- CORS policies restrict to trusted domains.
- CSRF protection on state-changing operations.
- Rate limiting on API endpoints.
- Session/cookie flags: `HttpOnly`, `Secure`, `SameSite`.
- Security headers present and correctly configured.

## Encryption
- Encryption in transit (TLS) for all connections.
- Encryption at rest for sensitive data.

## Outbound Request Controls
- Allowlists for permitted destinations.
- URL validation and sanitization for user-supplied URLs.
- Request timeouts and rate limits.

## Logging and Monitoring
- Security-relevant events logged without leaking sensitive data.
- Consistent logging schema for security events.
- Alertable events: login lifecycle, MFA changes, role changes, admin actions, exports.

## Least Privilege
- Database accounts use minimal required permissions.
- Service accounts follow least privilege principle.

## Frontend-Specific
- XSS prevention (prefer `textContent` over `innerHTML`).
- Clickjacking protection.
- Open redirect prevention.

## Mobile-Specific
- WebView security (trusted URLs only, HTTPS enforced).
- Insecure storage checks.
