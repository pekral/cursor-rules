---
name: test-like-human
description: Use when testing the current pull request. Find the
  'Testing Recommendations' section in the PR conversation and test
  the application like a senior web application tester. Follow the
  described scenarios, use tools when needed, and produce a human-readable
  report without technical notes.
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

**Constraint:**

-   For all GitHub operations, prefer GitHub CLI (`gh`) as the primary tool.
-   If `gh` is not available or cannot be used, use an available GitHub MCP server as fallback.
-   If neither `gh` nor a GitHub MCP server is available, stop and return a failed result explaining that required GitHub tools are missing.
-   Read project.mdc file!
-   First load all cursor editor rules (rules/.\*mdc).
-   I want the texts to be in the language in which the task was assigned. Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
-   **Before starting to test**, analyze all comments and discussions in the issue so that you fully understand what the final state should be and what logic should have been created. Only then begin testing.
-   Work only with the **current pull request**. Testing instructions must be taken only from the PR conversation.
-   Specifically search for a section named **'Doporučení k testování'** or **'Testing Recommendations'**. Prefer recommendations that include direct in-app links (full URLs) for fast click-through testing.
-   Test the application like a **senior tester of web applications who is not a programmer but works in a dev team and has access to developer tools**. Focus on visible behavior, usability, clarity, consistency, and real user experience. Do not focus on implementation details, internal architecture, or framework behavior — these must not appear in the final report.
-   When the change is primarily backend (models, services, actions, jobs, commands), verify the behavior by executing the relevant code paths directly via `php artisan tinker` or an equivalent CLI client — do not limit testing to the UI when a deeper verification is possible and useful.
-   Do not invent additional requirements outside the PR instructions unless needed to verify suspicious behavior.
-   API checks may use `curl` if needed. Interactive UI testing must use the available browser MCP tools.
-   For testing API endpoints follow steps defined in project.mdc section "## Testing API endpoints like human". Never run automatic tests from codebase!
-   When testing API endpoints, always load the project's API documentation first if it is available (e.g., OpenAPI/Swagger spec, Postman collection, README API section, API docs website).
    If no API documentation is available, find information about the endpoint via MCP (or otherwise). Use all available tools to obtain the necessary parameters for building the URL for the API!
-   The final output must be written for humans: no technical notes, terminal logs, stack details, or developer commentary.

------------------------------------------------------------------------

**Steps:**

1.  Load the current pull request using GitHub CLI (`gh`) first. If
    `gh` is not available, use a GitHub MCP server. If neither is
    available, stop and return a failed result about missing GitHub tools.
2.  Read the PR conversation: PR description, review comments, and discussion threads.
3.  Locate the **"Doporučení k testování" / "Testing Recommendations"** section and extract all testing instructions.
4.  If at least one extracted instruction requires API testing, first try to load the project's API documentation:
    -   Prefer local docs in the repo first (e.g., OpenAPI/Swagger JSON/YAML, Postman collection, or a README/API docs section).
    -   If local docs are not present, use MCP servers or installed CLI tools to locate API reference documentation.
    -   If no documentation can be found, proceed using MCP/other tools to discover endpoints as needed.
5.  Determine the **testing approach** for each instruction:
    -   **UI scenario** → use browser MCP tools
    -   **API scenario** → use `curl` or equivalent
    -   **Backend / code execution scenario** → use `php artisan tinker` or the project's equivalent CLI client
    -   **CLI scenario** → run the required terminal command
6.  Convert them into realistic **user scenarios** and think like a senior tester:
    -   what the user tries to achieve
    -   what could confuse the user
    -   where the flow could fail
    -   whether the behavior feels correct and trustworthy
    -   for backend changes: does the data end up in the correct state?

------------------------------------------------------------------------

**UI Testing**

If the instruction involves user interaction, use available browser MCP tools (navigation, snapshot, click, fill, wait, assert).

Simulate realistic user actions:

-   navigation
-   form interaction
-   submitting data
-   moving through application flows

Evaluate whether the flow behaves naturally and correctly.

------------------------------------------------------------------------

**API-Backed Scenarios**

If the behavior depends on API responses:

-   use `curl` only when necessary
-   always load API documentation first if it is available; otherwise find endpoint information via MCP or other available tools
-   verify that the user-visible behavior matches expectations
-   do not expose raw request/response details in the report

------------------------------------------------------------------------

**CLI-Supported Scenarios**

If the test requires terminal interaction:

-   run only what is necessary
-   use the results only to support conclusions
-   keep the final report human-readable

------------------------------------------------------------------------

**Backend Code Execution (Tinker & CLI Clients)**

Use when the change is primarily **backend logic** (models, services, actions, jobs, commands, or data transformations) that cannot be fully validated through the UI or an API endpoint alone.

When to use:

-   The changed code is not directly triggered by a user action in the browser.
-   The change affects data processing, business rules, or database state not visibly reflected in the UI.
-   A senior tester in a dev team would normally ask a developer to "run it in tinker" to confirm the result.

How to execute:

1.  Identify the entry point of the changed code (action class, model method, service, command, etc.) from the PR diff or description.
2.  Use `php artisan tinker` (or an equivalent CLI client) to set up the scenario:
    -   create or load the required model instances / test data (for Eloquent, prefer `Model::factory()`)
    -   invoke the changed class or method directly
    -   inspect the return value and the resulting database state
3.  Verify that:
    -   the output matches the expected behavior described in the PR
    -   database records are created, updated, or deleted as intended
    -   no unexpected side effects occur (e.g. duplicate records, wrong values, exceptions)
4.  Translate the technical result into a **human-readable conclusion** — focus on what changed from the user's perspective, not on the implementation details.

Rules:

-   Run only the minimum commands needed to validate the scenario.
-   Never modify production data; use test/seed data or a local development environment.
-   Do not expose raw tinker output in the final report — summarise the finding in plain language.
-   If tinker is not available, use the project's equivalent (Node.js REPL, Rails console, Django shell, etc.).

------------------------------------------------------------------------

**Test Result Format**

For each scenario:

``` markdown
## Scenario — Short Title

What was tested
Short description of the user goal.

Expected result
What a normal user would expect.

Observed result
What actually happened.

Status
Passed / Failed / Blocked / Unclear

Comment
Human-readable note focused on user experience.
```

------------------------------------------------------------------------

**Deliver**

Produce a human-readable markdown report containing:

-   pull request reference
-   tested scenarios with result for each
-   overall summary
-   list of failed / blocked / unclear behaviors
-   recommendation whether the change appears ready from a user perspective

------------------------------------------------------------------------

**After completing the tasks**

-   Post the final human-readable test report as a comment to the **related issue** in the issue tracker (GitHub issue, JIRA ticket, etc.). Use available CLI tools or MCP servers to post it. The comment must be written in the language of the task assignment.
-   Summarize which scenarios failed or were unclear (with technical info for the developer).
