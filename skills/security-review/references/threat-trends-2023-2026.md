# Threat Trends 2023-2026

## Identity Attack Resistance (ATO)
- Anti-automation controls for credential stuffing/spraying on login and recovery flows.
- Per-account and per-IP throttling.
- Step-up challenge on suspicious activity.
- Safe lockout behavior (no account enumeration via lockout).

## Password Reset and OTP Hardening
- Reset/OTP tokens are single-use.
- Tokens are short-lived.
- Protected by retry limits and abuse detection.

## BOLA/IDOR Regression Coverage
- Every endpoint with object identifiers has a negative authorization test.
- Out-of-scope IDs must return `403` or `404`.

## File Upload Hardening
- Extension allowlist (not blocklist).
- Content-type verification by actual file signature (magic bytes).
- Random file names (no user-controlled names).
- Storage outside webroot.
- Path traversal protections.

## Unsafe Deserialization and Parser Abuse
- No deserialization of untrusted input (`unserialize`, unsafe object parsers, unsafe binary formats) without strict type allowlists.
- Validation before deserialization.

## Supply Chain Controls
- SBOM generation per release.
- SCA in CI pipeline.
- Policy-based build failure for high-risk exploitable dependencies.
- Lockfile change review.

## Exploitability-Driven Remediation Priority
For CVE findings, evaluate using:
- `CVSS` severity
- `EPSS` probability
- `CISA KEV` presence
- Real exposure (internet-facing/reachable path)
- Asset criticality

## Urgency Thresholds for Patching
- Findings with KEV + internet exposure, or very high exploitability score, are escalated to immediate hotfix workflow.

## Secret Leak Response Readiness
- If leaked secrets are detected, incident flow includes:
  - Immediate revocation/rotation
  - Audit of dependent systems
