---
description: Read issue requirements, derive interactive browser test
  cases, confirm them with the user, and validate them step by step
  using the browser tool. Designed for acceptance criteria validation
  and exploratory UI testing directly from issue descriptions.
license: MIT
metadata:
  author: Petr Král (pekral.cz)
name: interactive-browser-testing
---

**Constraint:**
    Read project.md file
-   First, load all the rules for the cursor editor
    (.cursor/rules/.\*mdc).
-   Use the language in which the issue assignment was written.
-   Read the issue first and derive test cases only from the issue
    description, comments, attachments, linked documentation, and
    acceptance criteria.
-   Do not invent requirements that are not present in the issue
    context.
-   All verification must be executed interactively using the browser
    tool.
-   Prefer realistic end-user flows.
-   If credentials, environment URLs, or seed data are missing, report
    the blocker clearly.
-   If the issue includes multiple scenarios, test them individually.
-   If a scenario cannot be validated in the browser, mark it as blocked
    and explain why.
-   If possible, use the MCP server for project documentation and find out how to proceed with testing there. You should look up the instructions.

------------------------------------------------------------------------

**Steps:**

1.  Load the issue using installed CLI tools or available MCP servers. If you cannot load the issue, find out the available tools in the system and choose the most suitable tool to download the information.
2.  Read the full issue including:
    -   title
    -   description
    -   comments
    -   attachments
    -   linked pull requests
    -   acceptance criteria
3.  Extract expected **user-visible behavior**.
4.  Convert the issue into a structured list of **browser test cases**.
5.  For each test case define:
    -   goal
    -   preconditions
    -   user actions
    -   expected result

------------------------------------------------------------------------

**Confirmation Gate (Before Testing)**

Before executing tests:

-   Present all derived test cases to the user.
-   Ask for confirmation that the scenarios are correct.

The user must choose:

-   **Continue testing**
-   **Modify scenarios**
-   **Cancel testing**

Testing must not start until the user explicitly confirms the scenarios.

------------------------------------------------------------------------

**Interactive Browser Execution**

After confirmation:

1.  Open the relevant application environment in the browser tool.
2.  Execute each test case interactively.
3.  Simulate realistic user behavior:
    -   navigation
    -   form input
    -   UI actions
    -   button clicks
    -   page transitions
4.  Observe browser-visible outputs:
    -   UI updates
    -   validation messages
    -   redirects
    -   rendered data
    -   error states
5.  Compare observed behavior with expected results.

Mark each scenario as:

-   Passed
-   Failed
-   Blocked
-   Unclear

------------------------------------------------------------------------

**Exploratory Validation**

If a failure occurs:

-   Attempt minimal reproduction.
-   Verify surrounding UI behavior.
-   Confirm the failure is consistent.
-   Do not expand scope too far beyond the issue.

If the issue mentions regression risk:

-   explicitly validate the regression scenario.

------------------------------------------------------------------------

**Bug Report Mode**

If a failure is confirmed:

Prepare a structured bug report draft including:

-   Steps to reproduce
-   Expected behavior
-   Actual behavior
-   Impact / severity
-   Screens or UI observations if available

The report can be posted as:

-   issue comment
-   new bug issue

------------------------------------------------------------------------

**PR-Aware Mode**

If the issue is linked to a Pull Request:

1.  Load the PR description and summary.
2.  Review changes related to the issue.
3.  Focus browser testing on the flows affected by the PR.
4.  Validate that the PR implementation satisfies the issue
    requirements.

------------------------------------------------------------------------

**Test Case Format**

Each browser scenario must follow this structure:

``` markdown
## Test Case X — Short Title

Goal
What should be validated.

Preconditions
Required account, state, or environment.

Steps
1. Step one
2. Step two
3. Step three

Expected Result
Expected visible browser behavior.

Status
Passed / Failed / Blocked / Unclear

Notes
Observed behavior, blockers, or mismatches.
```

------------------------------------------------------------------------

**Validation Rules**

-   Test only what can be validated via browser interaction.
-   Do not assume backend correctness unless visible through UI
    behavior.
-   Do not mark a test as passed if results are ambiguous.
-   If requirements are unclear, mark the scenario as **Unclear**.
-   Prefer reproducible user actions over assumptions.

------------------------------------------------------------------------

**Deliver**

Produce a markdown report including:

-   issue reference
-   tested environment
-   derived test cases
-   per-test results
-   summary of:
    -   passed
    -   failed
    -   blocked
    -   unclear scenarios
-   key findings
-   recommended next actions

------------------------------------------------------------------------

**After Completing the Tasks**

-   Summarize whether the browser behavior matches the issue
    requirements.
-   List all failed or blocked scenarios.
-   Highlight the most important mismatch first.
-   If all relevant scenarios passed, state that the issue appears
    validated in the browser flow.
-   If according to @.cursor/skills/test-like-human/SKILL.md the changes can be tested, do it!
