# Example: Blocked — Insufficient Information to Proceed

## Phase 1: Understand

| Field | Value |
|---|---|
| **Classification** | Bug (tentative) |
| **Scope** | Unknown |
| **Constraints** | Cannot reproduce |

### Task checklist
- [x] Problem: "Dashboard loads slowly for some users"
- [ ] Open question: Which users are affected? (role, region, data volume?)
- [ ] Open question: What does "slowly" mean? (seconds, specific threshold?)
- [ ] Open question: When did this start? (recent deploy, data growth?)
- [x] Assumption: May be related to recent database migration

### Decision

**Blocked.** Cannot proceed to Propose phase — critical information is missing.

### Required clarifications
1. Affected user segment (role, region, account size)
2. Performance baseline and current measurements
3. Timeline of when the issue started
4. Whether the issue correlates with the recent database migration

### Next action
Requesting clarification from the reporter before continuing.
