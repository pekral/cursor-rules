# OWASP Top 10 Checklist

## OWASP Top 10 (2021)

- **A01 Broken Access Control** — Verify authorization on every endpoint; check for BOLA/IDOR, missing function-level access control, CORS misconfiguration.
- **A02 Cryptographic Failures** — Verify encryption in transit (TLS) and at rest; check for weak algorithms, missing key rotation, exposed sensitive data.
- **A03 Injection** — Check SQL, command, LDAP, XSS injection vectors; verify parameterized queries or ORM usage; no string concatenation with user input.
- **A04 Insecure Design** — Evaluate business logic flows for abuse potential; check threat modeling coverage.
- **A05 Security Misconfiguration** — Check default credentials, unnecessary features enabled, missing security headers, verbose error messages.
- **A06 Vulnerable and Outdated Components** — Check dependency versions, known CVEs, SCA results.
- **A07 Identification and Authentication Failures** — Check session management, password policies, MFA implementation, credential storage.
- **A08 Software and Data Integrity Failures** — Check CI/CD pipeline integrity, unsigned updates, unsafe deserialization.
- **A09 Security Logging and Monitoring Failures** — Verify security event logging, alerting, audit trail completeness.
- **A10 SSRF** — Check server-side request forgery vectors, URL validation, allowlists for outbound requests.

## OWASP API Security (2023)

- **API4:2023 Unrestricted Resource Consumption** — Expensive endpoints must have rate limits, quotas, pagination bounds, payload size limits, and return `429` when exceeded.

## OWASP A10:2025

- **Fail-secure exception handling** — Exception paths must not bypass authz/authn and must default to deny/abort.
