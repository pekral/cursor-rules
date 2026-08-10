# Problem Analysis

## 1. Summary

<!-- Short summary of the problem in 2–5 sentences.
Explain what is happening, where it is happening, and the most likely reason. -->

...

---

## 2. Problem Definition

**Problem:**
<!-- One precise sentence describing the actual problem. -->

...

**Expected behavior:**
<!-- What should have happened. -->

...

**Actual behavior:**
<!-- What is happening instead. -->

...

**Affected area:**
<!-- Module, feature, page, API endpoint, command, job, database table, external service, etc. -->

...

**Problem type:**
<!-- Bug / regression / performance / data issue / security / UX / unclear requirement / other -->

...

---

## 3. Verified Facts

<!-- Only confirmed information from the assignment, issue, comments, logs, screenshots, attachments, or code.
Do not put assumptions here. -->

- ...
- ...
- ...

---

## 4. Assumptions and Missing Information

### Assumptions

<!-- What we assume but cannot confirm. -->

- ...
- ...

### Missing Information

<!-- What would help verify the cause or the proposed solution. -->

- ...
- ...

---

## 5. Probable Root Cause

**Most probable cause:**
<!-- Clearly describe the root cause. -->

...

**Why this cause is probable:**

- ...
- ...
- ...

**Certainty level:**
<!-- High / Medium / Low -->

...

**Alternative possible causes:**

- ...
- ...

---

## 6. Problem Impact

### User / Business Impact

<!-- What the problem causes from the perspective of users, customers, support, or the business. -->

- ...
- ...

### Technical Impact

<!-- Impact on the application, data, performance, queues, cache, integrations, security, etc. -->

- ...
- ...

### Risk Areas

<!-- What can break or what to watch out for when fixing. -->

- ...
- ...

---

## 7. Recommended Solution

**Smallest safe solution:**
<!-- Describe the smallest effective fix. No unnecessary refactoring. -->

...

**Why this solution fits:**

- ...
- ...
- ...

**What to avoid:**

<!-- For example: large refactoring, architecture change, migration without reason, fixing symptoms instead of the cause. -->

- ...
- ...

**Possible side effects:**

- ...
- ...

---

## 8. Implementation Outline

<!-- Concrete technical direction, but without the implementation itself unless the user asked for code. -->

### Likely Change Locations

- `app/...`
- `routes/...`
- `database/...`
- `tests/...`

### Recommended Steps

1. ...
2. ...
3. ...

### Architecture Notes

<!-- If relevant, mention e.g. Action, Service, Repository, Validator, DTO, Job, etc. -->

- ...
- ...

---

## 9. Task Decomposition

<!-- Render this section ONLY when the recommended solution is too large for one change —
see the trigger rules in the skill's "Large-Task Decomposition Proposal" section.
When the solution fits a single change, DELETE this whole section (heading included).
Never leave a "no split needed" note, and never render it during a code-review invocation. -->

**Why this needs splitting:** <!-- one sentence naming the trigger that fired -->

...

**Tracker shape:** <!-- GitHub: EPIC parent + sub-issues · JIRA: parent issue + sub-tasks · Bugsnag: split proposed on the mirrored GitHub issue -->

...

**Parent:** <!-- existing issue / JIRA key that becomes the parent, or "to be created" + the proposed title -->

...

### Proposed parts

<!-- 2–8 parts. One part = one independently deliverable, independently reviewable unit that can merge
on its own. Split by deliverable, never by activity ("write the tests" is not a part).
More than 8 parts → hand the objective to @skills/blueprint/SKILL.md instead of listing them here. -->

| # | Part (proposed issue / sub-task title) | What it delivers | Depends on | Ships independently |
| - | -------------------------------------- | ---------------- | ---------- | ------------------- |
| 1 | ... | ... | — | yes |
| 2 | ... | ... | #1 | yes |

**Order and parallelism:** <!-- which parts are sequential, which may run in parallel, and any expand-before-contract constraint -->

...

**Handoff:** <!-- the exact next step — e.g. "run @skills/create-issues-from-text/SKILL.md to create the EPIC parent and the N sub-issues",
or for JIRA "create the N sub-tasks under <KEY>". This skill proposes only; it never creates or links tracker items. -->

...

---

## 10. Solution Verification

### Manual Verification

1. ...
2. ...
3. ...

### Automated Tests

<!-- Which tests to add or update. -->

- ...
- ...
- ...

### Edge Cases

<!-- Boundary situations the solution must cover. -->

- ...
- ...

### Regression Checks

<!-- What to check so the fix does not break existing behavior. -->

- ...
- ...

---

## 11. Non-Technical Explanation

<!-- Explanation for someone outside development: PM, support, client, product owner.
Without unnecessary technical detail. -->

...

---

## 12. Final Recommendation

<!-- Clearly state what should be done first and why. -->

**Recommendation:**
...

**Priority:**
<!-- Low / Medium / High / Critical -->

...

**Next step:**
...

---

## 13. Sources

<!-- Every source the analysis was actually built from. Mandatory — never leave empty.
List the issue / error and its comments and replies, linked / sub-issues, attachments,
code files, commits, and external URLs you consulted. If the only input was the inline
problem description with no issue-tracker source available, state that explicitly. -->

### Issue Tracker

<!-- Issue / PR / error URL, comment threads, linked & sub-issues, attachments. -->

- ...

### Codebase & Commits

<!-- Files, classes, and commits inspected (file:line, commit SHAs). -->

- ...

### External References

<!-- Documentation, advisories, or articles relied on. -->

- ...
