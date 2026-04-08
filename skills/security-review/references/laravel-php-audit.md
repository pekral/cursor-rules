# Laravel/PHP Incident-Response Audit

When the target is Laravel/PHP, include the following phases in the audit.

## Phase 1: Vulnerable Package Detection
- Analyze `composer.lock` and run `composer audit` (if available).
- Flag `intervention/image` versions lower than v3 as **Critical** (known path traversal risk).
- Check if vulnerable packages are direct or transitive dependencies.
- Evaluate safe upgrade strategy and potential breaking changes.

## Phase 2: Malware and Webshell Detection
- Search for PHP files in upload/public storage locations where execution should not be possible.
- Flag suspicious filenames and patterns (random 5-char names, common malware filenames, double extensions, temporary PHP droppers).
- Scan for dangerous PHP execution patterns (`eval`, `base64_decode`, `exec`, `system`, `shell_exec`, `assert`, etc.) in suspicious contexts.
- Check miner indicators (files/process names such as `xmrig`, `minerd`, `stmept`) and known pool references.
- Inspect `.htaccess` files for injected redirects, proxy directives, or shell-related artifacts.

## Phase 3: Configuration Security Check
- Verify `storage/app/public/.htaccess` blocks PHP execution.
- Verify `public/.htaccess` blocks PHP execution in public file areas and protects sensitive files.
- Review upload code for risky patterns (for example: `editableSvgs(true)`, unsafe filename preservation, weak MIME/extension checks).
- Inspect credential exposure risk in environment/config files without printing actual secret values.

## Phase 4: Server-Level Checks (When Access Exists)
- Verify web server/PHP processes run as unprivileged users.
- Audit risky sudoers entries (`NOPASSWD: ALL`).
- Check cron jobs for persistence/backdoor behavior.
- Check active suspicious processes and unusual open ports associated with miners/backdoors.

## Phase 5: Hardening Recommendations
- Propose safe hardening snippets for Apache/Nginx (deny PHP in upload/storage paths, add security headers, deny sensitive file patterns).
- For potentially breaking dependency remediations, provide a migration plan and impact notes.
- Never auto-apply changes in this skill; provide explicit, actionable recommendations only.
