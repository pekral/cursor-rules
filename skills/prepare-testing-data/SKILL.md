---
name: prepare-testing-data
description: "Reads project information and prepares test data relevant to the assigned task. Produces SQL and other artifacts so the programmer can test changes locally with minimal effort. Use when preparing test data for use cases, manual testing, or reproducing scenarios from issues."
license: MIT
metadata:
  author: "Petr Král (pekral.cz)"
---

**Constraint:**
- First, load all the rules for the cursor editor (.cursor/rules/.*mdc).
- Output in the same language as the assignment or user request.
- All messages formatted as markdown.

**Trigger:**
- User or assignment asks to prepare test data for a use case, scenario, or issue.
- Need to set up local data for manual testing or to verify changes.

**Steps:**

## 1. Gather project context

- Read available project information: schema (migrations, ER diagram), models, relationships, enums, factories, seeders.
- If the task comes from an issue or ticket, load its description, acceptance criteria, and comments to identify required entities and states.
- Identify which tables, columns, and relations are relevant to the scenario (e.g. orders, users, statuses).

## 2. Design minimal test dataset

- Define the minimal set of rows needed to cover the use case (e.g. one user, one order in “pending” state).
- Respect constraints: foreign keys, unique keys, non-null, enums. Use existing enums/constants from the codebase.
- Prefer deterministic, readable values (e.g. fixed IDs or names like `test-user`, `order-pending`) where it helps debugging.
- If the project uses factories/seeders, consider whether to recommend running them and then applying only delta SQL, or to provide a self-contained SQL file.

## 3. Produce SQL file

- Create a single SQL file (e.g. `tests/fixtures/<scenario-name>.sql` or `database/testing/<use-case>.sql`) that can be run in the target environment.
- Use `INSERT` (or `REPLACE`/`INSERT ... ON DUPLICATE KEY UPDATE` only if the project convention allows). Avoid `DELETE`/`TRUNCATE` unless the file is clearly meant for a fresh DB or dedicated test DB.
- Order statements so that parent rows exist before children (foreign key order).
- Add short comments per section (e.g. `-- User for scenario X`, `-- Orders in pending state`).
- If the DB uses different syntax (e.g. PostgreSQL), adapt accordingly.

## 4. Add any other artifacts

- If needed: small JSON/CSV fixtures, env vars, or a short list of steps (e.g. “1. Run `mysql < file.sql` 2. Log in as user X 3. Open path Y”). Keep it in the same folder or a short README next to the SQL file.
- Mention how to run the SQL (e.g. `mysql -u user -p database < file.sql` or `psql -f file.sql`) and any one-time setup (e.g. create test DB, run migrations first).

## 5. Summarize for the programmer

- In the reply, give a short summary: what scenario the data represents, which file(s) were created, and the exact command(s) to run so the programmer can test with minimal effort.
