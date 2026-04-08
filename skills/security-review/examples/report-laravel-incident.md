# Example: Laravel Incident-Response Audit Report

## Security Audit Report - LaravelApp (Incident Response)

### Phase 1: Vulnerable Packages

| Package | Installed | Safe Version | Severity | Direct? |
|---|---|---|---|---|
| intervention/image | 2.7.2 | 3.x | Critical | Yes |
| league/flysystem | 1.1.9 | 3.x | Medium | Transitive |

**Recommendation:** Upgrade `intervention/image` to v3. Review migration guide for breaking API changes in image manipulation calls.

### Phase 2: Malware/Webshell Scan

- **Found:** `storage/app/public/xkqzm.php` — 5-character random name, contains `eval(base64_decode(...))` pattern. **Likely webshell.**
- **Found:** `public/uploads/.htaccess` — contains `RewriteRule` redirecting to external domain. **Likely injected redirect.**
- **No miner indicators detected.**

### Phase 3: Configuration

- `storage/app/public/.htaccess` — **Missing.** PHP execution is not blocked in storage.
- Upload code in `app/Http/Controllers/MediaController.php:67` uses `editableSvgs(true)` — **risky**, allows SVG with embedded scripts.
- `.env` is in `.gitignore` — OK.
- `.env.example` contains placeholder values only — OK.

### Phase 4: Server-Level (SSH Available)

- Web server runs as `www-data` (unprivileged) — OK.
- **Found:** Sudoers entry `www-data ALL=(ALL) NOPASSWD: ALL` — **Critical.** Remove immediately.
- Cron: No suspicious entries found.
- No suspicious processes or unusual open ports detected.

### Phase 5: Hardening Recommendations

```apache
# storage/app/public/.htaccess — block PHP execution
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>
```

### Summary

| Severity | Count |
|---|---|
| Critical | 2 |
| High | 1 |
| Medium | 1 |
| Low | 0 |
| **Total** | **4** |

### Action Items

1. [ ] Remove `xkqzm.php` webshell and audit storage for additional malicious files
2. [ ] Remove injected `.htaccess` redirect in `public/uploads/`
3. [ ] Add `.htaccess` to block PHP execution in `storage/app/public/`
4. [ ] Remove `NOPASSWD: ALL` sudoers entry for `www-data`
5. [ ] Upgrade `intervention/image` to v3
6. [ ] Disable `editableSvgs(true)` in upload handling
