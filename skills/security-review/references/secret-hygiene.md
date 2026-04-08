# Secret Hygiene

## Environment Files
- `.env` must NOT be committed to version control.
- `.env.example` must contain placeholders only (no real values).
- Git history must be checked for leaked secrets.

## Response to Leaked Secrets
- Rotate any exposed secret immediately.
- Audit all systems that depend on the exposed secret.
- Update credentials in all environments.

## Detection
- Check source code for hardcoded credentials, API keys, tokens.
- Check configuration files for embedded secrets.
- Check CI/CD pipelines for secret exposure in logs.
- Never reveal actual secret values in reports; only report secret categories and exposure risk.
