---
name: tester-cookbook
description: "Use when preparing a human, click-by-click manual-testing cookbook for an internal QA tester from a JIRA task and its linked pull requests, and posting it as a JIRA comment."
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
- The cookbook audience is an internal QA tester who is not a programmer. Every step must be verifiable by clicking in the application, opening a report screen, reading a delivered email/SMS, or checking the account balance. Anything that cannot be verified that way belongs in the dedicated "What cannot be verified from the UI" section, with a concrete proposal for how the dev team will confirm it (forwarded notification, screenshot, etc.).
- **Forbidden vocabulary** in the cookbook body — replace with a UI-visible label before publishing:
  - infrastructure: `queue`, `lambda`, `SQS`, `ENV`, `.env`, `config`, `feature flag` (use *switch in Administration*), `job`, `dispatch`, `retry`, `polling`, `telemetry`, `log`, `Bugsnag`, `Slack` (unless the Slack notification is what the tester reads), `AWS`, `payload`, `endpoint`, `API v2` (unless that exact wording appears in the UI).
  - code identifiers: `enum`, `class`, `namespace`, `repository`, `action`, `table`, `column`, DB column name, migration, route, status code, event name, listener.
- **No code identifiers in the cookbook.** When the PR diff references a code-level value (for example `CampaignLogEvent::SMS_ACCEPTED`), translate it to the exact label the tester sees in the UI (*"status *Waiting for delivery* in the campaign report"*).
- **No credentials.** When a test account is required, refer to it by its tester-facing alias (*"use test account `qa-cz-1` — ask devs for access if you don't have it"*), never quote a password or API key.
- **Concrete inputs.** Replace generic phrasing with the literal value the tester should type. Instead of *"use a valid number"* write *"use the test number `+420604240203`"*.
- Validate the final comment **before** publishing: it must not contain any forbidden token, any Markdown heading (`#`), any fenced code block (` ``` `), or any Markdown table (`|`).

## Use when
- A JIRA task plus one or more linked GitHub pull requests need a manual testing cookbook that a non-technical QA tester can follow end-to-end.
- The expected delivery is a single JIRA comment in Wiki Markup posted to the originating task.
- The cookbook must cover happy path, at least one validation error, regression for any toggled feature/setting, and edge cases visible in the UI.

## Inputs
- `JIRA_KEY` — required. The JIRA task that owns the assignment (e.g. `ECOMAIL-1234`).
- `PR_NUMBER` — optional. A specific linked pull request to focus on; when omitted, use every PR linked from the JIRA task.

## Required approach

### 1. Load JIRA context
- Load the task via `skills/code-review-jira/scripts/load-issue.sh <KEY|URL>` — never call `acli` directly. If the loader is unavailable (missing tool, exit code 2/3), fall back to the JIRA MCP server.
- Read `summary`, `descriptionText`, every entry in `comments[]`, and the linked-PR list off the resulting JSON document.
- From description + comments, extract: the business requirement, the acceptance criteria, and any open questions.

### 2. Load each linked PR for impact analysis
- For every linked PR (or the explicitly provided `PR_NUMBER`), call `skills/code-review-github/scripts/load-issue.sh <NUMBER|URL>` — never call `gh pr view` directly.
- The PR diff is **input-only**. Its contents must not appear in the cookbook. From the diff, extract exclusively:
  - which screens or sections of the application are touched (admin / customer area / campaign report / contact detail / account settings / …);
  - which UI fields, switches, or buttons are new or behave differently;
  - which notifications the user or admin receives (email / SMS / push / in-app banner);
  - which entity states the user will see in the report (*Delivered*, *Invalid number*, *Waiting*, …);
  - which business rule changed (*credits are charged only for valid recipients*, …).
- Never copy class names, file paths, ENV keys, queue names, job names, enum cases, status codes (e.g. `sms_accepted`), DB column names, AWS service names, or Bugsnag references into the cookbook.

### 3. Map every change from code to UI
For each impact identified in step 2, look up the corresponding visible label inside the application:
- **Feature toggles** — find the switch label in the admin screen and write that label in the cookbook (e.g. *GoSMS API version*), never the feature key (e.g. `gosms_sms_version`).
- **State changes** — locate the localization string for the status and use the rendered text in the cookbook.
- **Internal-only changes (no UI footprint)** — add a "What cannot be verified from the UI" section and propose how the dev team will confirm it (forwarded notification, screenshot, etc.).

### 4. Compose the cookbook
Use the following structure. Skip a section that does not apply to the current task; do not invent sections that the task does not need.

- **What changes from the user's point of view** — 3–5 sentences in plain language describing what the user now sees in the application.
- **Setup before testing** — clickable steps only:
  - which type of account to test on (country, plan, role);
  - which dropdowns / checkboxes to flip in *Administration → …* (use the exact UI labels);
  - what the account must have ready (credits, sender phone channel, sender name, …).
- **Test scenarios** — numbered A, B, C, …. Each scenario contains:
  - **Precondition** — what the account state must be.
  - **Steps** — click-by-click navigation (*"Open Campaigns → New SMS campaign → …"*).
  - **Inputs** — concrete values (e.g. one valid CZ mobile number, one landline as the invalid case).
  - **Expected result** — what the tester sees in the UI, in the delivered message, in the report (always the rendered label, never the code-level state name).
  - Must cover happy path **and** at least one validation error.
- **Regression** — when the task introduces an admin switch or feature toggle, add a scenario with the toggle in its previous state (*"Switch the account back to version 1, repeat scenario A, the result must match the pre-change behavior."*).
- **Edge cases visible in the app** — large volumes (e.g. 500-contact list), combinations with other settings (different provider, different country), empty inputs — every case expressed as UI steps.
- **What to check after the run** — where the tester confirms the outcome:
  - Campaign report → specific tab / column.
  - Contact detail → specific section.
  - Delivered email / SMS — what the body should contain, how the sender should appear.
  - Credits / billing — how the balance should move.
- **What to report back to the dev team** — concrete visible symptoms (*"contacts stuck in status Waiting for over an hour"*, *"credits charged even for contacts marked Invalid number"*, *"the SMS arrived but the report shows Not delivered"*). Never list error codes, never mention Bugsnag.

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
- move the affected line into the "What cannot be verified from the UI" section.

Repeat until the body is clean. **Do not publish a comment that still contains forbidden vocabulary.**

### 7. Publish the comment
- Send via `acli` (primary): `acli jira comment <KEY> --noedit --comment="$(cat <cookbook-file>)"`.
- Fall back to the JIRA MCP server only when `acli` is unavailable.
- Never change the JIRA task status.

## Related skills (to disambiguate)
- `@skills/pr-summary/SKILL.md` — short, two-section business summary for PR / JIRA. Not a structured testing cookbook.
- `@skills/test-like-human/SKILL.md` — the agent runs the tests itself. This skill only writes instructions for a human tester.

## Output
- A single JIRA comment, in Wiki Markup, posted to the originating task.
- A short chat summary listing: JIRA task URL, total number of scenarios, and how many of them are happy-path / validation / regression / edge case.

## Example
**Wrong** (technical, leaks code identifiers):
> After sending the campaign, the `campaign_log` table receives a record with event `sms_accepted`, which the `ProcessSmsCampaignBatchAsyncResponsesJob` job flips to `sms_sent`.

**Right** (tester-facing, UI-anchored):
> After sending the campaign, open *Campaigns → campaign detail → Recipient activity*. The contact you just sent to first shows status *Waiting for delivery*. Refresh the page after 2–10 minutes — the status must change to *Delivered* (or *Invalid number* if the recipient was wrong).

## Done when
- A JIRA comment exists on the requested task.
- The comment includes at minimum: *What changes from the user's point of view*, *Setup before testing*, *Test scenarios*, *What to check after the run*, *What to report back to the dev team*.
- The comment contains no forbidden vocabulary, no Markdown headings, no code fences, and no Markdown tables.
- The chat output confirms the JIRA task URL and the scenario count breakdown (happy-path / validation / regression / edge case).
