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

-   Read project.mdc file!
-   First load all cursor editor rules (.cursor/rules/.\*mdc).
-   I want the texts to be in the language in which the task was assigned. Never combine multiple languages in your answer, e.g., one part in English and the other in Czech.
-   Analyze all comments in the issue tracker and check what needs to be done accordingly. Stick strictly to the assignment and comments!
-   Work only with the **current pull request**.
-   Testing instructions must be taken only from the PR conversation.
-   Specifically search for a section named **'Doporučení k testování'**
    or **'Testing Recommendations'**.
-   In that section, prefer recommendations that include direct in-app links
    (full URLs) for fast click-through testing.
-   Test the application like a **senior tester of web applications who
    is not a programmer but works in a dev team and has access to developer tools**.
-   Focus on visible behavior, usability, clarity, consistency, and real
    user experience.
-   When the change is primarily backend (models, services, actions, jobs,
    commands), verify the behavior by executing the relevant code paths
    directly via `php artisan tinker` or an equivalent CLI client — do not
    limit testing to the UI when a deeper verification is possible and useful.
-   Do not invent additional requirements outside the PR instructions
    unless needed to verify suspicious behavior.
-   Use available tools to complete testing, but keep the mindset
    non-technical and user-focused.
-   API checks may use `curl` if needed to complete the scenario.
-   Interactive UI testing must use existing **interactive browser
    testing skills**.
-   The final output must be written for humans and must not contain
    technical notes, logs, stack details, or developer commentary.
-   For testing api endpoints follow steps defined in project.mdc section "## Testing API endpoints like human". If you have a definition of the response from the API, check that the response from the test matches the documentation.
-   Never run automatic tests from codebase!
-   When testing API endpoints, always find information about the endpoint via MCP (or otherwise). Use all available tools to obtain the necessary parameters for building the URL for the API!

------------------------------------------------------------------------

**Steps**

1.  Load the current pull request using CLI tools or MCP servers.

2.  Read the PR conversation including:

    -   PR description
    -   review comments
    -   discussion threads

3.  Locate the section:

    **"Doporučení k testování" / "Testing Recommendations"**

4.  Extract all testing instructions.

5.  Determine the **testing approach** for each instruction:

    -   **UI scenario** → use interactive browser testing skill
    -   **API scenario** → use `curl` or equivalent
    -   **Backend / code execution scenario** → use `php artisan tinker`
        or the project's equivalent CLI client
    -   **CLI scenario** → run the required terminal command

6.  Convert them into realistic **user scenarios**.

7.  Think like a senior tester:

    -   what the user tries to achieve
    -   what could confuse the user
    -   where the flow could fail
    -   whether the behavior feels correct and trustworthy
    -   for backend changes: does the data end up in the correct state?

------------------------------------------------------------------------

**Testing Style**

Test like an experienced human tester:

-   behave like a real user
-   follow the natural user journey
-   observe messages, validation, navigation and feedback
-   notice unclear states or confusing outcomes
-   verify whether the application feels reliable and understandable

Do **not** test like a developer.

Avoid focusing on:

-   implementation details
-   internal architecture
-   framework behavior
-   low-level technical diagnostics in the final report

------------------------------------------------------------------------

**Testing Execution**

Execute tests according to their type.

### UI Testing

If the instruction involves user interaction:

Use the interactive browser testing skill:

@.cursor/skills/interactive-testing/SKILL.md

Simulate realistic user actions:

-   navigation
-   form interaction
-   submitting data
-   moving through application flows

Evaluate whether the flow behaves naturally and correctly.

------------------------------------------------------------------------

### API-Backed Scenarios

If the behavior depends on API responses:

-   use `curl` only when necessary
-   When testing API endpoints, always find information about the endpoint via MCP (or otherwise). Use all available tools to obtain the necessary parameters for building the URL for the API!
-   verify that the user-visible behavior matches expectations
-   do not expose raw request/response details in the report

------------------------------------------------------------------------

### CLI-Supported Scenarios

If the test requires terminal interaction:

-   run only what is necessary
-   use the results only to support conclusions
-   keep the final report human-readable

------------------------------------------------------------------------

### Backend Code Execution (Tinker & CLI Clients)

Use this approach when the change is primarily **backend logic** (models,
services, actions, jobs, commands, or data transformations) that cannot
be fully validated through the UI or an API endpoint alone.

**When to use:**

-   The changed code is not directly triggered by a user action in the browser.
-   The change affects data processing, business rules, or database state
    in a way that is not visibly reflected in the UI within the test session.
-   A senior tester in a dev team would normally ask a developer to
    "run it in tinker" to confirm the result is correct.

**How to execute:**

1.  Identify the entry point of the changed code (action class, model
    method, service, command, etc.) by reading the PR diff or description.
2.  Use `php artisan tinker` (or an equivalent CLI client available in
    the project) to set up the scenario:
    -   create or load the required model instances / test data
    -   invoke the changed class or method directly
    -   inspect the return value and the resulting database state
3.  Verify that:
    -   the output matches the expected behavior described in the PR
    -   database records are created, updated, or deleted as intended
    -   no unexpected side effects occur (e.g. duplicate records, wrong
        values, exceptions)
4.  Translate the technical result into a **human-readable conclusion**
    for the report — focus on what changed from the user's perspective,
    not on the implementation details.

**Rules:**

-   Run only the minimum commands needed to validate the scenario.
-   Never modify production data; use test/seed data or a local
    development environment.
-   Do not expose raw tinker output in the final report — summarise the
    finding in plain language.
-   If tinker is not available or the project uses a different runtime
    (Node.js REPL, Rails console, Django shell, etc.), use the equivalent
    tool.

------------------------------------------------------------------------

**Failure Handling**

If a scenario fails:

-   describe what the tester tried to do
-   explain what was expected
-   explain what actually happened
-   describe why it might confuse or harm the user experience

If behavior is inconsistent or unclear:

-   mark it clearly
-   explain the uncertainty in plain language

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
-   tested scenarios
-   result for each scenario
-   overall summary
-   list of failed / blocked / unclear behaviors
-   recommendation whether the change appears ready from a user
    perspective

------------------------------------------------------------------------

**Output Rules**

The final report must:

-   be easy to read for humans
-   avoid technical notes
-   avoid terminal logs
-   avoid stack-specific vocabulary
-   avoid code-oriented reasoning
-   sound like feedback from a senior manual tester

------------------------------------------------------------------------

**After completing the tasks**

Summarize:

-   which scenarios failed or were unclear (with technical info)

-   Post the final human-readable test report as a comment to the **related issue** in the issue tracker (GitHub issue, JIRA ticket, etc.). Use available CLI tools or MCP servers to post it. The comment must be written in the language of the task assignment.
