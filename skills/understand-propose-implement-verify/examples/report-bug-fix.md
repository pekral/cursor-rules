# Example: Bug Fix — API returns 500 on empty payload

## Phase 1: Understand

| Field | Value |
|---|---|
| **Classification** | Bug |
| **Scope** | `src/api/UserController.ts` |
| **Constraints** | Must not break existing valid payloads |

### Task checklist
- [x] Problem: POST /users returns 500 when body is empty
- [x] Assumption: No input validation before service call
- [ ] Open question: Should empty payload return 400 or 422?

---

## Phase 2: Propose

**Chosen solution:** Add input validation middleware that returns 400 for empty/missing body fields.

| Criterion | Assessment |
|---|---|
| Scope | Minimal — one middleware addition |
| Safety | Fully reversible |
| Conventions | Project already uses validation middleware pattern |
| Skill reuse | `create-test` for adding validation tests |

**Trade-offs:** None significant. Alternative (inline validation) rejected as it deviates from project conventions.

---

## Phase 3: Implement

- Added validation schema for POST /users endpoint
- Added middleware binding in route configuration
- Created test cases for empty body, missing required fields, valid payload
- Invoked `create-test` skill for test generation

---

## Phase 4: Verify

| Check | Status |
|---|---|
| Tests pass | Yes (14/14) |
| Linter clean | Yes |
| Requirements met | Yes — empty payload now returns 400 |
| Regressions | None detected |

### Skills used
- `create-test` — generated validation test cases

### Remaining risks
- None identified
