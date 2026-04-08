# Example: Multiple PRs for One Issue

## Issue #200 — Implement user notifications

### PR #201 — feat(notifications): add email notification service

#### Critical

- **`src/Services/EmailNotifier.php:34`** — SMTP credentials are hardcoded. Move to environment configuration.

#### Moderate

- **`src/Services/EmailNotifier.php:56-70`** — Retry logic duplicates the pattern in `src/Services/SmsNotifier.php:40-54`. Extract a shared retry helper.

---

### PR #202 — feat(notifications): add in-app notification UI

**No findings were identified.**

---

### Consolidated overview

| PR | Result |
|---|---|
| #201 — email notification service | Has findings (1 Critical, 1 Moderate) |
| #202 — in-app notification UI | Clean |
