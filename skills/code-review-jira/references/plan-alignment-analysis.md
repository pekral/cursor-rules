# Plan Alignment and Code Quality Analysis

## Plan Alignment Analysis

Compare the implementation against the original issue description, planning documents, or step description:

1. Identify deviations from the planned approach, architecture, or requirements
2. Assess whether deviations are justified improvements or problematic departures
3. Verify that all planned functionality has been implemented
4. List any missing or only partially met items

## Simplification Analysis

Evaluate whether the solution can be written more simply without altering the new logic, leveraging rules and conventions already defined in `rules/**/*.mdc`. Flag unnecessary complexity as a finding.

## Regression Analysis

For every changed file:

1. Check whether the modifications could break existing functionality that is NOT part of the ticket scope
2. Trace callers and dependents of changed methods/classes
3. If a change alters shared logic (helpers, services, traits, base classes, interfaces), verify that all consumers still behave correctly
4. Flag any regression risk as a finding — even if the new code is correct in isolation, breaking unrelated features is **Critical**
