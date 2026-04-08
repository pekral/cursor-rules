# Example: Full Audit Report

## Security Audit Report - ExampleApp

### Critical

1. **SQL Injection in Search Endpoint**
   - **Category:** A03 Injection
   - **Location:** `app/Http/Controllers/SearchController.php:22`
   - **Description:** User input from `?q=` parameter is concatenated directly into a raw SQL query.
   - **Exploit:** Attacker can extract database contents via UNION-based injection.
   - **Fix:** Use parameterized query or Eloquent builder.

### High

1. **Missing CSRF Protection on Profile Update**
   - **Category:** A05 Security Misconfiguration
   - **Location:** `routes/web.php:45`
   - **Description:** The profile update route is excluded from CSRF middleware.
   - **Exploit:** Attacker can craft a malicious form on an external site to change victim's profile.
   - **Fix:** Remove the route from the `$except` array in `VerifyCsrfToken` middleware.

### Medium

1. **Missing Content-Security-Policy Header**
   - **Category:** A05 Security Misconfiguration
   - **Location:** Server configuration / middleware
   - **Description:** No CSP header is set, allowing unrestricted script sources.
   - **Fix:** Add CSP middleware with restrictive policy.

### Low

1. **Server Version Exposed in Response Headers**
   - **Category:** A05 Security Misconfiguration
   - **Location:** Server configuration
   - **Description:** `X-Powered-By: PHP/8.2.0` header is present in responses.
   - **Fix:** Disable `expose_php` in `php.ini`.

### Summary

| Severity | Count |
|---|---|
| Critical | 1 |
| High | 1 |
| Medium | 1 |
| Low | 1 |
| **Total** | **4** |

### Confidence Notes

- Server-level checks (Phase 4) were not performed due to lack of SSH access.
- `composer audit` was not available; package analysis based on `composer.lock` parsing only.

### Action Items

1. [ ] Fix SQL injection in SearchController immediately
2. [ ] Restore CSRF protection on profile update route
3. [ ] Add CSP header via middleware
4. [ ] Disable `expose_php` in production `php.ini`
