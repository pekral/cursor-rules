# Example: Feature — Add rate limiting to public API

## Phase 1: Understand

| Field | Value |
|---|---|
| **Classification** | Feature |
| **Scope** | `src/middleware/`, `src/config/` |
| **Constraints** | Must not affect authenticated endpoints; must be configurable per route |

### Task checklist
- [x] Problem: Public API endpoints have no rate limiting, risk of abuse
- [x] Requirement: Configurable rate limits per route
- [x] Requirement: Return 429 with Retry-After header when exceeded
- [x] Assumption: Redis is available for distributed rate tracking

---

## Phase 2: Propose

**Chosen solution:** Sliding window rate limiter using Redis, applied via middleware decorator.

**Alternatives considered:**
1. Fixed window counter — simpler but allows burst at window boundary (rejected)
2. Token bucket — more complex than needed for this use case (rejected)

| Criterion | Assessment |
|---|---|
| Scope | New middleware + config, no changes to existing endpoints |
| Safety | Can be disabled via config flag |
| Conventions | Follows existing middleware pattern |
| Skill reuse | `create-test` for tests, `security-review` for rate limit bypass risks |

**Risks:** Redis unavailability could block requests. Mitigation: fail-open with logging.

---

## Phase 3: Implement

- Created `RateLimitMiddleware` with sliding window algorithm
- Added rate limit configuration schema
- Applied middleware to all public routes
- Added fail-open fallback when Redis is unavailable
- Created integration and unit tests
- Invoked `create-test` and `security-review` skills

---

## Phase 4: Verify

| Check | Status |
|---|---|
| Tests pass | Yes (23/23) |
| Linter clean | Yes |
| Requirements met | Yes — all 3 requirements satisfied |
| Regressions | None detected |
| Security review | Passed — no bypass vectors identified |

### Skills used
- `create-test` — generated test cases for rate limit scenarios
- `security-review` — verified no bypass vectors

### Remaining risks
- Redis failover latency may cause brief rate limit gaps (accepted risk, documented)

### Confidence notes
- Fail-open behavior is a deliberate trade-off: availability over strict enforcement
