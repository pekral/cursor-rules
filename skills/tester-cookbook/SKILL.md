---
name: tester-cookbook
description: "Use when preparing a concise QA report for an internal tester from a JIRA task and its linked pull requests — focused on what the tester should report back to the dev team — and posting it as a JIRA comment."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

## Constraints
- Apply `@rules/php/core-standards.mdc`
- Apply `@rules/git/general.mdc`
- Apply `@rules/jira/general.mdc` — JIRA comments must be in Wiki Markup, never Markdown
- Output language must match the language of the JIRA task (e.g. `ECOMAIL-*` tasks → Czech). Do not mix languages within a single comment.
- Read-only relative to the codebase. The skill never modifies code; it only publishes a JIRA comment.
- Never change the JIRA task status — per `@rules/jira/general.mdc`, status transitions are handled by humans only.
- The audience is an internal QA tester who is not a programmer. Everything in the report must be verifiable by clicking in the application, opening a report screen, reading a delivered email/SMS, or checking the account balance. Anything that cannot be verified that way belongs in the dev-team report as a flag for the development team, not as a tester action.
- **Forbidden vocabulary** in the comment body — replace with a UI-visible label before publishing:
  - infrastructure: `queue`, `lambda`, `SQS`, `ENV`, `.env`, `config`, `feature flag` (use *switch in Administration*), `job`, `dispatch`, `retry`, `polling`, `telemetry`, `log`, `Bugsnag`, `Slack` (unless the Slack notification is what the tester reads), `AWS`, `payload`, `endpoint`, `API v2` (unless that exact wording appears in the UI).
  - code identifiers: `enum`, `class`, `namespace`, `repository`, `action`, `table`, `column`, DB column name, migration, route, status code, event name, listener.
- **No code identifiers in the comment.** When the PR diff references a code-level value (for example `CampaignLogEvent::SMS_ACCEPTED`), translate it to the exact label the tester sees in the UI (*"status *Waiting for delivery* in the campaign report"*).
- **No credentials.** When a test account is required, refer to it by its tester-facing alias (*"use test account `qa-cz-1` — ask devs for access if you don't have it"*), never quote a password or API key.
- **Concrete inputs.** Replace generic phrasing with the literal value the tester should type. Instead of *"use a valid number"* write *"use the test number `+420604240203`"*.
- Validate the final comment **before** publishing: it must not contain any forbidden token, any Markdown heading (`#`), any fenced code block (` ``` `), or any Markdown table (`|`).

## Use when
- A JIRA task plus one or more linked GitHub pull requests need a short, tester-facing QA report.
- The expected delivery is a single JIRA comment in Wiki Markup posted to the originating task.
- The dev team needs a clear, concrete list of symptoms the tester should report back if observed; the tester optionally needs a brief click-path to reach the affected area.

## Inputs
- `JIRA_KEY` — required. The JIRA task that owns the assignment (e.g. `ECOMAIL-1234`).
- `PR_NUMBER` — optional. A specific linked pull request to focus on; when omitted, use every PR linked from the JIRA task.

## Required approach

### 1. Load JIRA context
- Load the task via `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>` — never call `acli` directly. If the loader is unavailable (missing tool, exit code 2/3), fall back to the JIRA MCP server.
- Read `summary`, `descriptionText`, every entry in `comments[]`, and the linked-PR list off the resulting JSON document.

### 2. Load each linked PR for impact analysis
- For every linked PR (or the explicitly provided `PR_NUMBER`), call `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — never call `gh pr view` directly.
- The PR diff is **input-only**. Its contents must not appear in the comment. From the diff, extract exclusively:
  - which screen / section / report the change is visible in;
  - which entity states the tester will see in the UI (*Delivered*, *Invalid number*, *Waiting*, …);
  - which business rule changed (*credits are charged only for valid recipients*, …);
  - which notifications the user or admin receives (email / SMS / push / in-app banner).
- Never copy class names, file paths, ENV keys, queue names, job names, enum cases, status codes, DB column names, AWS service names, or Bugsnag references into the comment.

### 3. Map every change from code to UI
For each impact identified in step 2, look up the corresponding visible label inside the application:
- **State changes** — locate the localization string for the status and use the rendered text in the comment.
- **Feature toggles** — find the switch label in the admin screen and write that label (e.g. *GoSMS API version*), never the feature key (e.g. `gosms_sms_version`).
- **Internal-only changes (no UI footprint)** — surface them in the dev-team report ("ask dev team to confirm X"); do not invent UI steps the tester cannot perform.

### 4. Compose the comment
Every comment opens with two metadata lines (in JIRA Wiki Markup), then the body sections. The body skips the first section when it does not add value (for example when the change is verifiable purely from the dev-team report — a notification text change, a label rename in the report).

**Metadata lines (always at the top of the comment, in this order):**

- *Authors:* the real change author(s) — JIRA display name when the JIRA loader can match the committer, otherwise the GitHub handle `@handle`, otherwise the git `Name <email>` form. Comma-separated in commit order, deduped. Resolved exactly as `@skills/pr-summary/SKILL.md` resolves authors (`git log --pretty='%an <%ae>' base..HEAD`, plus PR `author.login` and `commits[].author.login`). Never list the agent / publishing identity. When authorship cannot be determined, write *Authors: unknown — git history did not yield a recognisable identity*.
- *Available behind:* present only when the verified change is reachable only behind a test parameter (admin switch label _GoSMS API version_, ENV {{BETA_PRICING=1}}, query {{?preview=1}}, feature toggle, allow-listed account). Name the switch label exactly as it appears in the admin UI when one exists (per the forbidden-vocabulary rule — UI labels, not feature keys). When the change is reachable for every user unconditionally, omit the line entirely.

**Body sections:**

- **Brief steps to reach the result** (optional) — at most a handful of bullets, each one a single click-path line, just enough for the tester to land on the affected screen. No precondition tables, no scenario enumeration, no edge-case matrices. When *Available behind* is set, the **first** bullet must be the click-path that enables the gating switch. Example: *"Open Administration → switches → enable *GoSMS API version v2* → Campaigns → new SMS campaign → send to test number `+420604240203` → open *Recipient activity* on the campaign detail."*
- **What to report back to the dev team** (required) — concrete visible symptoms the tester should flag if observed, written so the tester only needs to recognise them in the UI. Each bullet is one symptom, framed in plain language: *"contacts stuck in status *Waiting* for over an hour"*, *"credits charged even for contacts marked *Invalid number*"*, *"the SMS arrived but the report shows *Not delivered*"*, *"the *GoSMS API version* switch is missing from the admin screen"*. Never list error codes, never mention Bugsnag, never reference internal class or queue names.

### 5. Convert to JIRA Wiki Markup
- Headings: `h2.`, `h3.` (never `#`).
- Bullets: `*`. Numbered lists: `#`.
- Bold: `*bold*`. Italic: `_italic_`.
- UI labels (button names, menu items) are bold (*Open Campaigns*), **not** wrapped in `{{...}}` — `{{...}}` reads as code and disrupts a non-technical reader.
- Use `{{...}}` only for literal strings the tester types verbatim, e.g. the test phone number `{{+420604240203}}`.
- No code fences (` ``` `), no Markdown headings, no Markdown tables. The full conversion cheatsheet lives in `@rules/jira/general.mdc`.

### 6. Pre-publish validation
Before sending the comment, scan the body for every forbidden token listed in **Constraints**. When a forbidden token is found, either:
- replace it with the UI label discovered in step 3, or
- rewrite the affected line as a dev-team-report bullet ("ask dev team to confirm …").

Repeat until the body is clean. **Do not publish a comment that still contains forbidden vocabulary.**

### 7. Publish the comment
- Send via `acli` (primary): `acli jira comment <KEY> --noedit --comment="$(cat <report-file>)"`.
- Fall back to the JIRA MCP server only when `acli` is unavailable.
- Never change the JIRA task status.

## Related skills (to disambiguate)
- `@skills/pr-summary/SKILL.md` — short, two-section business summary for PR / JIRA. Different audience (project managers, not QA testers).
- `@skills/test-like-human/SKILL.md` — the agent runs the tests itself. This skill only writes a tester-facing report.
- **interactive-testing** (when available in the host environment, not shipped with this package) — interactive browser-driven validation. Different flow: an agent walks through scenarios live in a browser instead of producing written instructions for a human tester.

## Output
- A single JIRA comment, in Wiki Markup, posted to the originating task.
- A short chat summary listing the JIRA task URL, whether the *Brief steps* section was included, and the number of dev-team-report bullets.

## Example
**Wrong** (technical, leaks code identifiers):
> After sending the campaign, the `campaign_log` table receives a record with event `sms_accepted`, which the `ProcessSmsCampaignBatchAsyncResponsesJob` job flips to `sms_sent`.

**Right** (tester-facing, dev-team-report focused):
> *Report back to the dev team if you see:* the SMS arrived on the phone but the campaign report shows *Not delivered* after refreshing; credits charged for a contact whose status ends up as *Invalid number*; the *Recipient activity* tab is empty even though the campaign was sent.

## Done when
- A JIRA comment exists on the requested task.
- The comment includes the required **What to report back to the dev team** section, and optionally the **Brief steps to reach the result** section when it adds value.
- The comment contains no forbidden vocabulary, no Markdown headings, no code fences, and no Markdown tables.
- The chat output confirms the JIRA task URL, whether *Brief steps* was included, and the number of dev-team-report bullets.
