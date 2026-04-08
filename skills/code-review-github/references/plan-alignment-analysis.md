# Plan Alignment Analysis

## Purpose

Compare the PR implementation against the original issue description, planning documents, or step description to detect deviations.

## Procedure

1. **Collect the plan** — Read the linked issue body, any referenced planning documents, and step descriptions.
2. **List planned items** — Extract every distinct requirement or task from the plan.
3. **Map implementation to plan** — For each planned item, identify the corresponding code change in the PR diff.
4. **Classify deviations:**
   - **Justified improvement** — The implementation departs from the plan but delivers a better outcome (simpler, more robust, covers an edge case). Document why.
   - **Problematic departure** — The implementation contradicts the plan without clear justification. Flag as a finding.
   - **Missing functionality** — A planned item has no corresponding implementation. Flag as a finding.
   - **Partially met** — A planned item is only partly implemented. Flag as a finding with details on what is missing.
5. **Report** — Include deviation findings in the appropriate severity level (Critical if it breaks requirements, Moderate if it reduces scope, Minor if cosmetic).

## Notes

- Analyze all comments in the issue to build a complete task list, not just the issue body.
- If significant deviations are found, explicitly flag them and ask for confirmation before concluding.
- If the original plan or requirements themselves appear flawed, recommend updates.
