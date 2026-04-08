# Example: Bugsnag Issue Blocked by Code Review

## Bugsnag Issue BSN-5103 — TypeError in PaymentGateway::processRefund

| Field | Value |
|---|---|
| **Decision** | `blocked` |
| **Bugsnag ID** | BSN-5103 |
| **Branch** | fix/bsn-5103-refund-type-error |
| **Root cause** | `processRefund` receives string amount from webhook instead of expected float |
| **TDD status** | Red-green cycle completed |
| **Code review** | 1 Critical finding, 1 Moderate finding |
| **CI status** | All checks passed |
| **Coverage** | 100% of changed lines covered |

### Code review findings

- **Critical** — `src/Payment/PaymentGateway.php:112`: Type cast without validation; malformed string input would silently produce zero refund amount. Must validate before casting.
- **Moderate** — `src/Payment/PaymentGateway.php:118`: Missing logging for refund amount mismatch between request and processed value.

### Blocking reason

Code review cycle has unresolved Critical and Moderate findings. Fixes must be applied and CR re-run before PR creation.

### Next action

Fix the two findings above, re-run `@skills/code-review-github/SKILL.md`, and repeat until clean.
