# Severity Levels

## Critical
Exploitable vulnerabilities requiring immediate action.
- Injection (SQL, command, XSS with data exfiltration)
- Authentication bypass
- Direct data exposure of sensitive records
- Remote code execution
- Known exploited CVEs (CISA KEV)

## High
Significant risks that should be addressed promptly.
- Missing CSRF protection on state-changing operations
- Weak CORS configuration allowing credential sharing
- Open redirects usable in phishing chains
- Broken access control (BOLA/IDOR)
- Missing authorization on sensitive endpoints

## Medium
Best practice violations that increase attack surface.
- Missing security headers (CSP, HSTS, X-Content-Type-Options)
- Weak input validation
- Verbose error messages exposing internals
- Missing rate limiting
- Stale dependencies with known but unexploitable CVEs

## Low
Minor improvements for defense in depth.
- Logging gaps for security events
- Informational leaks (server version, framework version)
- Missing `rel="noopener noreferrer"` on external links
- Non-critical cookie flags missing
